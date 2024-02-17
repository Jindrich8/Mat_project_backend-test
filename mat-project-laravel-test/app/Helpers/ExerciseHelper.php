<?php

namespace App\Helpers;

use App\Exceptions\UnsupportedVariantException;
use App\Helpers\Database\DBHelper;
use App\Helpers\Database\DBJsonHelper;
use App\Helpers\Exercises\FillInBlanks\FillInBlanksExerciseHelper;
use App\Helpers\Exercises\FixErrors\FixErrorsExerciseHelper;
use App\Models\Exercise;
use App\Models\SavedTask;
use App\Types\EvaluateExercise;
use App\Types\TakeExercise;
use App\Utils\DebugUtils;
use App\Utils\Utils;
use DateTimeZone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExerciseHelper
{
  private static array $exerciseHelpers = [];

  /**
   * @param ExerciseType $type
   * @return CExerciseHelper
   * @throws UnsupportedVariantException
   */
  public static function getHelper(ExerciseType $type): CExerciseHelper
  {
    return match ($type) {
      ExerciseType::FillInBlanks =>
      self::$exerciseHelpers[$type->name] ??= new FillInBlanksExerciseHelper(),

      ExerciseType::FixErrors =>
      self::$exerciseHelpers[$type->name] ??= new FixErrorsExerciseHelper(),

      default => throw new UnsupportedVariantException($type),
    };
  }

  /**
   * @template R
   * @template T
   * @param int $taskId
   * @param callable(CExerciseHelper,int[],mixed[]):array<int,T> $fetchConcreteOnes
   * @param callable(int $id,string|null $instructions,T $cExercise):R $toClass
   * @param ?Carbon $localySavedTaskUtcTimestamp
   * @param bool $shouldFetchInstructions
   * @return R[]
   * @throws UnsupportedVariantException
   */
  public static function fetchRealExercises(int $taskId, callable $fetchConcreteOnes, callable $toClass, ?Carbon $localySavedTaskUtcTimestamp = null, bool $shouldFetchInstructions = true): array
  {
    if ($localySavedTaskUtcTimestamp?->isUtc() === false) {
      $localySavedTaskUtcTimestamp->setTimezone(DateTimeZone::UTC);
    }

    $exerciseIDName = Exercise::getPrimaryKeyName();
    $columns = [$exerciseIDName, Exercise::EXERCISEABLE_TYPE];
    if ($shouldFetchInstructions) {
      $columns[] = Exercise::INSTRUCTIONS;
    }
    /**
     * @var array<array|\stdClass> $exercises
     */
    $exercises = DB::table(Exercise::getTableName())
      ->select($columns)
      ->where(Exercise::TASK_ID, $taskId)
      ->orderBy(Exercise::ORDER)
      ->get()
      ->all();
    DebugUtils::log("Exercises: ", $exercises);
    $user = Auth::user();
    $savedExercises = [];
    if ($user !== null) {
      $savedTaskTable = SavedTask::getTableName();
      $builder = DB::table($savedTaskTable)
        ->select([SavedTask::DATA])
        ->where(SavedTask::USER_ID, '=', $user->id)
        ->where(SavedTask::TASK_ID, '=', $taskId);
      if ($localySavedTaskUtcTimestamp) {
        $builder = $builder
          ->where(SavedTask::UPDATED_AT, '>', $localySavedTaskUtcTimestamp, boolean: 'or')
          ->where(SavedTask::CREATED_AT, '>', $localySavedTaskUtcTimestamp);
      }
      $savedTaskData = $builder
        ->value(SavedTask::DATA);
      if ($savedTaskData) {
        $decodedSavedData = DBJsonHelper::decode(
          $savedTaskData,
          table: $savedTaskTable,
          column: SavedTask::DATA,
          id: [SavedTask::USER_ID => $user->id, SavedTask::TASK_ID => $taskId]
        );
        if (is_array($decodedSavedData) && array_is_list($decodedSavedData)) {
          $savedExercises = $decodedSavedData;
        } else {
          DBHelper::deleteWCompositeKey(
            [
              SavedTask::USER_ID => $user->id,
              SavedTask::TASK_ID => $taskId
            ],
            table: $savedTaskTable,
            try: true
          );
        }
      }
    }
    /**
     * @var array<string,array{int[],mixed[]}> $map
     */
    $map = [];
    $exercisesCount = count($exercises);
    for ($i = 0; $i < $exercisesCount; ++$i) {
      $exercise = $exercises[$i];
      $exerciseType = Utils::access($exercise, Exercise::EXERCISEABLE_TYPE);
      /**
       * @var int $exerciseId
       */
      $exerciseId = Utils::access($exercise, $exerciseIDName);

      $map[$exerciseType][0][] = $exerciseId;
      $map[$exerciseType][1][] = $savedExercises ? Utils::arrayShift($savedExercises) : null;
    }
    $cExercises = [];
    /**
     * @var array<string,array{int[],mixed[]}> $map
     */
    foreach ($map as $exerciseType => $idsAndSavedValues) {
      DebugUtils::log("Ids and saved values for {$exerciseType} ", $idsAndSavedValues);
      $cExercises += $fetchConcreteOnes(
        ExerciseHelper::getHelper(ExerciseType::from($exerciseType)),
        $idsAndSavedValues[0],
        $idsAndSavedValues[1]
      );
    }

    /**
     * @var array<int,array{id:int,instructions:string,impl:CTakeExercise,type:string}> $result
     */
    $result = [];
    while (($exercise = Utils::arrayShift($exercises))) {
      $exerciseId = Utils::access($exercise, $exerciseIDName);
      $result[] = $toClass(
        $exerciseId,
        Utils::tryToAccess($exercise, Exercise::INSTRUCTIONS, default: null),
        $cExercises[$exerciseId]
      );
    }
    return $result;
  }

  /**
   * @param int $taskId
   * @param ?Carbon $localySavedTaskUtcTimestamp
   * @return TakeExercise[]
   * @throws UnsupportedVariantException
   */
  public static function take(int $taskId, ?Carbon $localySavedTaskUtcTimestamp = null): array
  {

    return  ExerciseHelper::fetchRealExercises(
      taskId: $taskId,
      /**
       * @param CExerciseHelper $helper
       * @param int[] $ids
       * @return CTakeExercise[]
       */
      fetchConcreteOnes: fn (CExerciseHelper $helper, array $ids, array $savedValues) =>
      $helper->fetchTake($ids, $savedValues),

      toClass: fn (int $id, ?string $instructions, CTakeExercise $impl) =>
      new TakeExercise($id, $instructions, $impl),
      localySavedTaskUtcTimestamp: $localySavedTaskUtcTimestamp,
      shouldFetchInstructions: true
    );
  }

  /**
   * @param int $taskId
   * @return EvaluateExercise[]
   */
  public static function evaluate(int $taskId): array
  {
    $exerciseIDName = Exercise::getPrimaryKeyName();
    /**
     * @var array<array|\stdClass> $exercises
     */
    $exercises = DB::table(Exercise::getTableName())
      ->select([$exerciseIDName,Exercise::WEIGHT, Exercise::EXERCISEABLE_TYPE, Exercise::INSTRUCTIONS])
      ->where(Exercise::TASK_ID, $taskId)
      ->orderBy(Exercise::ORDER)
      ->get()
      ->all();
    DebugUtils::log("Exercises: ", $exercises);
    /**
     * @var array<string,array{int[],mixed[]}> $map
     */
    $map = [];
    $exercisesCount = count($exercises);
    for ($i = 0; $i < $exercisesCount; ++$i) {
      $exercise = $exercises[$i];
      $exerciseType = Utils::access($exercise, Exercise::EXERCISEABLE_TYPE);
      /**
       * @var int $exerciseId
       */
      $exerciseId = Utils::access($exercise, $exerciseIDName);

      $map[$exerciseType][] = $exerciseId;
    }
    $cExercises = [];
    /**
     * @var array<string,array{int[],mixed[]}> $map
     */
    foreach ($map as $exerciseType => $ids) {
      DebugUtils::log("Ids for {$exerciseType} ", $ids);
      $cExercises +=
        ExerciseHelper::getHelper(ExerciseType::from($exerciseType))
        ->fetchEvaluate($ids);
    }

    /**
     * @var array<int,array{id:int,instructions:string,impl:CTakeExercise,type:string}> $result
     */
    $result = [];
    while (($exercise = Utils::arrayShift($exercises))) {
      $exerciseId = Utils::access($exercise, $exerciseIDName);
      $result[] = new EvaluateExercise(
        id: $exerciseId,
        weight:Utils::access($exercise,Exercise::WEIGHT),
        instructions: Utils::access($exercise, Exercise::INSTRUCTIONS),
        impl: $cExercises[$exerciseId]
      );
    }
    return $result;
  }
}
