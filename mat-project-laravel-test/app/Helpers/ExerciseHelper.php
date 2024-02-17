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
use App\Dtos\InternalTypes\TaskSaveContent;
use App\Types\SavedTaskContentProvider;
use App\Types\SaveTask;
use App\Utils\DebugUtils;
use App\Utils\DtoUtils;
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
   * @param callable(CExerciseHelper,array<int,mixed>):array<int,T> $fetchConcreteOnes
   * @param callable(int $id,string|null $instructions,T $cExercise):R $toClass
   * @param ?Carbon $localySavedTaskUtcTimestamp
   * @param bool $shouldFetchInstructions
   * @return R[]
   * @throws UnsupportedVariantException
   */
  public static function fetchRealExercises(int $taskId, callable $fetchConcreteOnes, callable $toClass, ?SavedTaskContentProvider $savedTask = null, bool $shouldFetchInstructions = true): array
  {

    $exerciseIDName = Exercise::getPrimaryKeyName();
    $columns = [$exerciseIDName, Exercise::EXERCISEABLE_TYPE];
    if ($shouldFetchInstructions) {
      $columns[] = Exercise::INSTRUCTIONS;
    }
    $exercises = DB::table(Exercise::getTableName())
      ->select($columns)
      ->where(Exercise::TASK_ID, $taskId)
      ->orderBy(Exercise::ORDER)
      ->get()
      ->all();
    DebugUtils::log("Exercises: ", $exercises);
    $savedExercises = $savedTask?->getContent()->exercises ?? [];
    /**
     * @var array<string,array<int,mixed>> $map array of exercise type to array of exercise id to saved value or null
     */
    $map = [];
    $exercisesCount = count($exercises);
    for ($i = 0; $i < $exercisesCount; ++$i) {
      $exercise = $exercises[$i];
      /**
       * @var string $exerciseType
       */
      $exerciseType = DBHelper::access($exercise, Exercise::EXERCISEABLE_TYPE);
      /**
       * @var int $exerciseId
       */
      $exerciseId = DBHelper::access($exercise, $exerciseIDName);
      $savedExercise = $savedExercises ? Utils::arrayShift($savedExercises) : null;
      $mapByExerciseType = &$map[$exerciseType];
      $mapByExerciseType[$exerciseId][]=$savedExercise;
    }
    $cExercises = [];
    foreach ($map as $exerciseType => $savedValuesByIds) {
      DebugUtils::log("Ids and saved values for {$exerciseType} ", $savedValuesByIds);
      $cExercises += $fetchConcreteOnes(
        ExerciseHelper::getHelper(ExerciseType::from($exerciseType)),
        $savedValuesByIds
      );
    }

    /**
     * @var array<int,array{id:int,instructions:string,impl:CTakeExercise,type:string}> $result
     */
    $result = [];
    while (($exercise = Utils::arrayShift($exercises))) {
      /**
       * @var int $exerciseId
       */
      $exerciseId = DBHelper::access($exercise, $exerciseIDName);
      $result[] = $toClass(
        $exerciseId,
        DBHelper::tryToAccess($exercise, Exercise::INSTRUCTIONS, default: null),
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
  public static function take(int $taskId, ?SavedTaskContentProvider $savedTask): array
  {

    return  ExerciseHelper::fetchRealExercises(
      taskId: $taskId,
      /**
       * @param CExerciseHelper $helper
       * @param array<int,mixed> $savedValues indexed by exercise id
       * @return CTakeExercise[]
       */
      fetchConcreteOnes: fn (CExerciseHelper $helper,array $savedValues) =>
      $helper->fetchTake($savedValues),

      toClass: fn (int $id, ?string $instructions, CTakeExercise $impl) =>
      new TakeExercise($id, $instructions, $impl),
      savedTask:$savedTask,
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
      $exerciseType = DBHelper::access($exercise, Exercise::EXERCISEABLE_TYPE);
      /**
       * @var int $exerciseId
       */
      $exerciseId = DBHelper::access($exercise, $exerciseIDName);

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
      $exerciseId = DBHelper::access($exercise, $exerciseIDName);
      $result[] = new EvaluateExercise(
        id: $exerciseId,
        weight:DBHelper::access($exercise,Exercise::WEIGHT),
        instructions: DBHelper::access($exercise, Exercise::INSTRUCTIONS),
        impl: $cExercises[$exerciseId]
      );
    }
    return $result;
  }
}
