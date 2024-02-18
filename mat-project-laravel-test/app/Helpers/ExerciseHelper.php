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
use App\ModelConstants\ExerciseConstants;
use App\Types\SavedTaskContentProvider;
use App\Types\SaveTask;
use App\Utils\DebugUtils;
use App\Utils\DtoUtils;
use App\Utils\Utils;
use DateTimeZone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;

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
    $helper = self::tryGetHelper($type);
    if(!$helper){
      throw new UnsupportedVariantException($type);
    }
    return $helper;
  }

  public static function tryGetHelper(ExerciseType $type):CExerciseHelper|null{
    return match ($type) {
      ExerciseType::FillInBlanks =>
      self::$exerciseHelpers[$type->name] ??= new FillInBlanksExerciseHelper(),

      ExerciseType::FixErrors =>
      self::$exerciseHelpers[$type->name] ??= new FixErrorsExerciseHelper(),

      default => null,
    };
  }

  public static function getHelpers(){
    foreach(ExerciseType::cases() as $case){
      $helper = self::tryGetHelper($case);
      if($helper){
        yield $helper;
      }
    }
  }

  /**
   * @template R
   * @template T
   * @param int $taskInfoId
   * @param callable(CExerciseHelper,array<int,mixed>):array<int,T> $fetchConcreteOnes
   * @param callable(int $id,string|null $instructions,T $cExercise):R $toClass
   * @param ?Carbon $localySavedTaskUtcTimestamp
   * @param bool $shouldFetchInstructions
   * @return R[]
   * @throws UnsupportedVariantException
   */
  public static function fetchRealExercises(int $taskInfoId, callable $fetchConcreteOnes, callable $toClass, ?SavedTaskContentProvider $savedTask = null, bool $shouldFetchInstructions = true): array
  {

    $exerciseIDName = ExerciseConstants::COL_ID;
    $columns = [$exerciseIDName, ExerciseConstants::COL_EXERCISEABLE_TYPE];
    if ($shouldFetchInstructions) {
      $columns[] = ExerciseConstants::COL_INSTRUCTIONS;
    }
    $exercises = DB::table(ExerciseConstants::TABLE_NAME)
      ->select($columns)
      ->where(ExerciseConstants::COL_TASK_INFO_ID, $taskInfoId)
      ->orderBy(ExerciseConstants::COL_ORDER)
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
      $exerciseType = DBHelper::access($exercise, ExerciseConstants::COL_EXERCISEABLE_TYPE);
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
        DBHelper::tryToAccess($exercise, ExerciseConstants::COL_INSTRUCTIONS, default: null),
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
  public static function takeTaskInfo(int $taskInfoId, ?SavedTaskContentProvider $savedTask): array
  {

    return  ExerciseHelper::fetchRealExercises(
      taskInfoId: $taskInfoId,
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
   * @param int $taskInfoId
   * @return EvaluateExercise[]
   */
  public static function evaluateTaskInfo(int $taskInfoId): array
  {
    $exerciseIDName = ExerciseConstants::COL_ID;
    /**
     * @var array<array|stdClass> $exercises
     */
    $exercises = DB::table(ExerciseConstants::TABLE_NAME)
      ->select([
        $exerciseIDName,
      ExerciseConstants::COL_WEIGHT,
      ExerciseConstants::COL_EXERCISEABLE_TYPE,
      ExerciseConstants::COL_INSTRUCTIONS
      ])
      ->where(ExerciseConstants::COL_TASK_INFO_ID, $taskInfoId)
      ->orderBy(ExerciseConstants::COL_ORDER)
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
      $exerciseType = DBHelper::access($exercise, ExerciseConstants::COL_EXERCISEABLE_TYPE);
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
        weight:DBHelper::access($exercise,ExerciseConstants::COL_WEIGHT),
        instructions: DBHelper::access($exercise, ExerciseConstants::COL_INSTRUCTIONS),
        impl: $cExercises[$exerciseId]
      );
    }
    return $result;
  }
}
