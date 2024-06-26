<?php

namespace App\Helpers\Exercises\FixErrors;

use App\Dtos\Defs\Exercises\FixErrors\FixErrorsTakeResponse;
use App\Dtos\Defs\Exercises\FixErrors\FixErrorsTakeResponseContent;
use App\Helpers\CCreateExerciseHelper;
use App\Helpers\CExerciseHelper;
use App\Helpers\Database\DBHelper;
use App\ModelConstants\FixErrorsConstants;
use App\Models\FixErrors;
use Illuminate\Support\Facades\DB;

class FixErrorsExerciseHelper implements CExerciseHelper
{
    private ?CreateFixErrorsExercise $createHelper;

    public function __construct()
    {
        $this->createHelper = null;
    }

    public function fetchTake(array $savedValues): array
    {
        $ids = array_keys($savedValues);
        $table = FixErrorsConstants::TABLE_NAME;
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

    public function fetchEvaluate(array $ids): array
    {
        $table = FixErrorsConstants::TABLE_NAME;
        $idName = FixErrors::getPrimaryKeyName();
        $fixErrorsExercises = DB::table($table)
            ->select([
                $idName,
            FixErrorsConstants::COL_CORRECT_TEXT,
            FixErrorsConstants::COL_WRONG_TEXT,
            FixErrorsConstants::COL_DISTANCE
            ])
            ->whereIn($idName, $ids)
            ->get();
            unset($ids);
        $evaluateExercises = [];
        while (($fixErrorsExercise = $fixErrorsExercises->shift()) !== null) {
            $exerciseId = DBHelper::access($fixErrorsExercise,$idName);
            /**
             * @var string $correctText
             */
            $correctText = DBHelper::access($fixErrorsExercise,FixErrorsConstants::COL_CORRECT_TEXT);

             /**
             * @var string $wrongText
             */
            $wrongText = DBHelper::access($fixErrorsExercise,FixErrorsConstants::COL_WRONG_TEXT);
            /**
             * @var int $distance
             */
            $distance = DBHelper::access($fixErrorsExercise,FixErrorsConstants::COL_DISTANCE);
            $evaluateExercises[$exerciseId] = new EvaluateFixErrorsExercise(
                correctText:$correctText,
                defaultText:$wrongText,
                defaultDistance:$distance
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

    public function delete(array $ids): void
    {
        DB::table(FixErrorsConstants::TABLE_NAME)
        ->whereIn(FixErrorsConstants::COL_EXERCISEABLE_ID,$ids)
        ->delete();
    }
}
