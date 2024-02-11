<?php

namespace App\Helpers\Exercises\FixErrors;

use App\Dtos\Defs\Exercises\FixErrors\FixErrorsTakeResponse;
use App\Dtos\Defs\Exercises\FixErrors\FixErrorsTakeResponseContent;
use App\Helpers\CCreateExerciseHelper;
use App\Helpers\CExerciseHelper;
use App\Models\FixErrors;
use App\Utils\Utils;
use Illuminate\Support\Facades\DB;

class FixErrorsExerciseHelper implements CExerciseHelper
{
    private ?CreateFixErrorsExercise $createHelper;

    public function __construct(){
        $this->createHelper = null;
    }

    public function fetchTake(array $ids,array $savedValues): array
    {
        $table = FixErrors::getTableName();
        $idName = FixErrors::getPrimaryKeyName();
       $exercises = DB::table($table)
        ->select([$idName,FixErrors::WRONG_TEXT])
        ->whereIn($idName,$ids)
        ->get();
        $takeExercises = [];
        while(($exercise = $exercises->pop())){
            $exerciseId = DBHelper::access($exercise,$idName);

          $takeExercises[$exerciseId]= new  TakeFixErrorsExercise(
            FixErrorsTakeResponse::create()
            ->setContent(FixErrorsTakeResponseContent::create()
            ->setDefaultText(DBHelper::access($exercise,FixErrors::WRONG_TEXT)))
           );
        }
        return $takeExercises;
    }

    public function fetchEvaluate(array $ids): array
    {
        $table = FixErrors::getTableName();
        $idName = FixErrors::getPrimaryKeyName();
       $exercises = DB::table($table)
        ->select([$idName,FixErrors::WRONG_TEXT])
        ->whereIn($idName,$ids)
        ->get();
        $evaluateExercises = [];
        while(($exercise = $exercises->pop())){
            $exerciseId = DBHelper::access($exercise,$idName);

          $evaluateExercises[$exerciseId]= new  EvaluateFixErrorsExercise(
            FixErrorsEvaluateResponse::create()
            ->setContent(FixErrorsEvaluateResponseContent::create()
            ->setDefaultText(DBHelper::access($exercise,FixErrors::WRONG_TEXT)))
           );
        }
        return $evaluateExercises;
    }

    public function fetchSave(array $ids): array
    {
        return [];
    }

    public function getCreateHelper(): CCreateExerciseHelper
    {
        return $this->createHelper ??= new CreateFixErrorsExercise();
    }
}
