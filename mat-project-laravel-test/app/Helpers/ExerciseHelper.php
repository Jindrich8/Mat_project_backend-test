<?php

namespace App\Helpers;

use App\Exceptions\UnsupportedVariantException;
use App\Helpers\Database\DBHelper;
use App\Helpers\Exercises\FillInBlanks\FillInBlanksExerciseHelper;
use App\Helpers\Exercises\FixErrors\FixErrorsExerciseHelper;
use App\Types\EvaluateExercise;
use App\Types\TakeExercise;
use App\ModelConstants\ExerciseConstants;
use App\Types\SavedTaskContentProviderInterface;
use App\Types\StopWatchTimer;
use App\Utils\DebugLogger;
use App\Utils\Utils;
use Carbon\Carbon;
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

  public static function getHelpers(): \Generator
  {
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
   * @param int $taskSourceId
   * @param callable(CExerciseHelper,array<int,mixed>):array<int,T> $fetchConcreteOnes
   * @param callable(int $id,string|null $instructions,T $cExercise):R $toClass
   * @param ?Carbon $localySavedTaskUtcTimestamp
   * @param bool $shouldFetchInstructions
   * @return R[]
   * @throws UnsupportedVariantException
   */
  public static function fetchRealExercises(int $taskSourceId, callable $fetchConcreteOnes, callable $toClass, ?SavedTaskContentProviderInterface $savedTask = null, bool $shouldFetchInstructions = true): array
  {

    $exerciseIDName = ExerciseConstants::COL_ID;
    $columns = [$exerciseIDName, ExerciseConstants::COL_EXERCISEABLE_TYPE];
    if ($shouldFetchInstructions) {
      $columns[] = ExerciseConstants::COL_INSTRUCTIONS;
    }
    $exercises = DB::table(ExerciseConstants::TABLE_NAME)
      ->select($columns)
      ->where(ExerciseConstants::COL_TASK_SOURCE_ID, $taskSourceId)
      ->orderBy(ExerciseConstants::COL_ORDER)
      ->get()
      ->all();
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
      $savedExercise = $savedExercises ? array_shift($savedExercises) : null;
      $mapByExerciseType = &$map[$exerciseType];
      $mapByExerciseType[$exerciseId]=$savedExercise;
    }
    $cExercises = [];
    foreach ($map as $exerciseType => $savedValuesByIds) {
      $cExercises += $fetchConcreteOnes(
        ExerciseHelper::getHelper(ExerciseType::from($exerciseType)),
        $savedValuesByIds
      );
    }

    /**
     * @var array<int,array{id:int,instructions:string,impl:CTakeExercise,type:string}> $result
     */
    $result = [];
    while (($exercise = array_shift($exercises))) {
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
   * @param int $taskSourceId
   * @param ?Carbon $localySavedTaskUtcTimestamp
   * @return TakeExercise[]
   * @throws UnsupportedVariantException
   */
  public static function takeTaskSource(int $taskSourceId, ?SavedTaskContentProviderInterface $savedTask): array
  {

    return StopWatchTimer::run("takeTaskSource",fn()=> ExerciseHelper::fetchRealExercises(
      taskSourceId: $taskSourceId,
      /**
       * @param CExerciseHelper $helper
       * @param array<int,mixed> $savedValues indexed by exercise id
       * @return CTakeExercise[]
       */
      fetchConcreteOnes: fn (CExerciseHelper $helper,array $savedValues) =>StopWatchTimer::run($helper::class." fetchTake",fn()=>
      $helper->fetchTake($savedValues)),

      toClass: fn (int $id, ?string $instructions, CTakeExercise $impl) =>
      new TakeExercise($id, $instructions, $impl),
      savedTask:$savedTask,
      shouldFetchInstructions: true
    ));
  }

  /**
   * @param int $taskSourceId
   * @return EvaluateExercise[]
   */
  public static function evaluateTaskSource(int $taskSourceId): array
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
      ->where(ExerciseConstants::COL_TASK_SOURCE_ID, $taskSourceId)
      ->orderBy(ExerciseConstants::COL_ORDER)
      ->get()
      ->all();
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
      $cExercises +=
        ExerciseHelper::getHelper(ExerciseType::fromThrow($exerciseType))
        ->fetchEvaluate($ids);
    }
    unset($map);
    /**
     * @var array<int,array{id:int,instructions:string,impl:CTakeExercise,type:string}> $result
     */
    $result = [];
    while (($exercise = Utils::arrayShift($exercises))) {
      $exerciseId = DBHelper::access($exercise, $exerciseIDName);
      $type = DBHelper::access($exercise,ExerciseConstants::COL_EXERCISEABLE_TYPE);
      $result[] = new EvaluateExercise(
        id: $exerciseId,
        weight:DBHelper::access($exercise,ExerciseConstants::COL_WEIGHT),
        instructions: DBHelper::access($exercise, ExerciseConstants::COL_INSTRUCTIONS),
        type:ExerciseType::fromThrow($type),
        impl: $cExercises[$exerciseId]
      );
    }
    return $result;
  }
}
