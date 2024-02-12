<?php

namespace App\Helpers\Exercises\FixErrors;

use App\Dtos\Defs\Exercises\FixErrors\FixErrorsReviewResponse;
use App\Dtos\Defs\Exercises\FixErrors\FixErrorsReviewResponseContent;
use App\Dtos\Defs\Exercises\FixErrors\FixErrorsTakeResponse;
use App\Dtos\Defs\Exercises\FixErrors\FixErrorsTakeResponseContent;
use App\Helpers\CCreateExerciseHelper;
use App\Helpers\CExerciseHelper;
use App\Helpers\Database\DBHelper;
use App\Models\FixErrors;
use App\Utils\Utils;
use Illuminate\Support\Facades\DB;

class FixErrorsExerciseHelper implements CExerciseHelper
{
    private ?CreateFixErrorsExercise $createHelper;

    public function __construct()
    {
        $this->createHelper = null;
    }

    public function fetchTake(array &$savedValues): array
    {
        $ids = array_keys($savedValues);
        $table = FixErrors::getTableName();
        $idName = FixErrors::getPrimaryKeyName();
        $exercises = DB::table($table)
            ->select([$idName, FixErrorsConstants::COL_WRONG_TEXT])
            ->whereIn($idName, $ids)
            ->get();
        $takeExercises = [];
        reset($savedValues);
        $savedValue = current($savedValues);
        while (($exercise = $exercises->pop())) {
            $exerciseId = DBHelper::access($exercise, $idName);
            $content = FixErrorsTakeResponseContent::create()
                ->setDefaultText(DBHelper::access($exercise, FixErrorsConstants::COL_WRONG_TEXT));
            if (is_string($savedValue)) {
                $content->setText($savedValue);
            }

            $takeExercises[$exerciseId] = new TakeFixErrorsExercise(
                FixErrorsTakeResponse::create()
                    ->setContent($content)
            );
        }
        return $takeExercises;
    }

    public function fetchEvaluate(array &$ids): array
    {
        $table = FixErrors::getTableName();
        $idName = FixErrors::getPrimaryKeyName();
        $exercises = DB::table($table)
            ->select([$idName, FixErrorsConstants::COL_WRONG_TEXT])
            ->whereIn($idName, $ids)
            ->get();
        $evaluateExercises = [];
        while (($exercise = $exercises->pop())) {
            $exerciseId = DBHelper::access($exercise, $idName);

            $evaluateExercises[$exerciseId] = new  EvaluateFixErrorsExercise(
                FixErrorsReviewResponse::create()
                    ->setContent(FixErrorsReviewResponseContent::create()
                        ->setUserText(DBHelper::access($exercise, FixErrorsConstants::COL_WRONG_TEXT)))
            );
        }
        return $evaluateExercises;
    }

    public function fetchSave(array &$ids): array
    {
        return [];
    }

    public function getCreateHelper(): CCreateExerciseHelper
    {
        return $this->createHelper ??= new CreateFixErrorsExercise();
    }
}
