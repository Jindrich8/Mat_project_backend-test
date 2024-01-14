<?php

namespace App\Helpers\Exercises\FixErrors;

use App\Dtos\Task\Take\Response\FixErrorsTakeResponse;
use App\Dtos\Task\Take\Response\FixErrorsTakeResponseContent;
use App\Helpers\CCreateExerciseHelper;
use App\Helpers\CExerciseHelper;
use App\Helpers\CTakeExercise;
use App\Helpers\Exercises\FixErrors\CreateFixErrorsExercise;
use App\Models\FixErrors;
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
          $takeExercises[$exercise[$idName]]= new  TakeFixErrorsExercise(
            FixErrorsTakeResponse::create()
            ->setContent(FixErrorsTakeResponseContent::create()
            ->setDefaultText($exercise[FixErrors::WRONG_TEXT]))
           );
        }
        return $takeExercises;
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
