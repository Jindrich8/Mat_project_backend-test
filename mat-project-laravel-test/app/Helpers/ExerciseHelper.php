<?php

namespace App\Helpers;

use App\Exceptions\UnsupportedVariantException;
use App\Helpers\Exercises\FillInBlanks\FillInBlanksExerciseHelper;
use App\Helpers\Exercises\FixErrors\FixErrorsExerciseHelper;
use App\Models\Exercise;
use App\Types\TakeExercise;
use Illuminate\Support\Facades\DB;

class ExerciseHelper
{
    private static array $exerciseHelpers =[];

    public static function addHelper(CExerciseHelper $helper, ExerciseType $type):CExerciseHelper{
        return ExerciseHelper::$exerciseHelpers[$type->name]??=$helper;
    }

    /**
     * @param ExerciseType $type
     * @return CExerciseHelper
     * @throws UnsupportedVariantException
     */
  public static function getHelper(ExerciseType $type):CExerciseHelper{
      return match($type){
          ExerciseType::FillInBlanks =>
          ExerciseHelper::addHelper(new FillInBlanksExerciseHelper(),$type),

          ExerciseType::FixErrors =>
          ExerciseHelper::addHelper(new FixErrorsExerciseHelper(),$type),

            default => throw new UnsupportedVariantException(ExerciseType::class,$type->name),
      };
  }

    /**
     * @template R
     * @template T
     * @param int $taskId
     * @param callable(CExerciseHelper,int[]):array<int,T> $fetchConcreteOnes
     * @param callable(int,string|null,T,string):R $toClass
     * @param bool $shouldFetchInstructions
     * @return R[]
     * @throws UnsupportedVariantException
     */
  public static function fetchRealExercises(int $taskId, callable $fetchConcreteOnes, callable $toClass,bool $shouldFetchInstructions = true):array
  {

      $columns = [Exercise::ID,Exercise::EXERCISEABLE_TYPE];
      if($shouldFetchInstructions){
          $columns[]=Exercise::INSTRUCTIONS;
      }
      /**
       * @var array<int,array<string,string>> $wtf
       */
      $exercises = DB::table('exercises')
          ->select($columns)
          ->where(Exercise::TASK_ID, $taskId)
          ->orderBy(Exercise::ORDER)
          ->get()
          ->toArray();

      /**
       * @var array<string,int[]> $map
       */
      $map = [];
      foreach ($exercises as $exercise) {
          $map[$exercise[Exercise::EXERCISEABLE_TYPE]][] = $exercise[Exercise::ID];
      }
      $cExercises = [];
      foreach ($map as $key => $value) {
          $cExercises += $fetchConcreteOnes(ExerciseHelper::getHelper(ExerciseType::from($key)), $value);
      }

      /**
       * @var array<int,array{id:int,instructions:string,impl:CTakeExercise,type:string}> $result
       */
      $result = [];
      foreach ($exercises as $exercise) {
          $result[] = $toClass(
              $exercise[Exercise::ID],
              $exercise[Exercise::INSTRUCTIONS] ?? null,
              $cExercises[$exercise[Exercise::ID]],
              $exercise[Exercise::EXERCISEABLE_TYPE]);
      }
      return $result;
  }

    /**
     * @return TakeExercise[]
     * @throws UnsupportedVariantException
     */
    public static function take(int $taskId): array
    {

    return  ExerciseHelper::fetchRealExercises(
          $taskId,
        /**
         * @param CExerciseHelper $helper
         * @param int[] $ids
         * @return CTakeExercise[]
         */
          fn(CExerciseHelper $helper, array $ids) => $helper->fetchTake($ids),
        fn($id,$instructions,$impl,$type) => new TakeExercise($id,$instructions,$type,$impl)
    );
  }
}