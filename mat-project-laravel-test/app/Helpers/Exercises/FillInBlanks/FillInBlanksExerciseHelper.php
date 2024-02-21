<?php

namespace App\Helpers\Exercises\FillInBlanks;

use App\Dtos\Defs\Exercises\FillInBlanks\FillInBlanksTakeResponse as FillInBlanksFillInBlanksTakeResponse;
use App\Dtos\InternalTypes\FillInBlanksContent;
use App\Dtos\InternalTypes;
use App\Helpers\CCreateExerciseHelper;
use App\Helpers\CExerciseHelper;
use App\Dtos\Defs\Exercises\FillInBlanks\Combobox;
use App\Dtos\Defs\Exercises\FillInBlanks\TextInput;
use App\Exceptions\InternalException;
use App\Helpers\Database\DBHelper;
use App\ModelConstants\FillInBlanksConstants;
use App\Utils\DtoUtils;
use App\Utils\Utils;
use DB;
use Generator;

class FillInBlanksExerciseHelper implements CExerciseHelper
{
    private ?CreateFillInBlanksExercise $createHelper;

    public function __construct()
    {
        $this->createHelper = null;
    }

    /**
     * @param int[] $ids
     * @return Generator<int, FillInBlanksContent, mixed, void>
     */
    private static function fetchContents(array $ids): Generator
    {
        $table = FillInBlanksConstants::TABLE_NAME;
        $idName = FillInBlanksConstants::COL_EXERCISEABLE_ID;
        $exercises = DB::table($table)
            ->select([$idName, FillInBlanksConstants::COL_CONTENT])
            ->whereIn($idName, $ids)
            ->get();
        while (($exercise = $exercises->pop()) !== null) {
            /**
             * @var int $exerciseId
             */
            $exerciseId = DBHelper::access($exercise, $idName);


            $content = DtoUtils::importDto(
                dto: FillInBlanksContent::class,
                json: DBHelper::access($exercise, FillInBlanksConstants::COL_CONTENT),
                table: $table,
                column: FillInBlanksConstants::COL_CONTENT,
                id: $exerciseId,
                wrapper: FillInBlanksContent::CONTENT
            );
            yield $exerciseId => $content;
        }
    }

    public function fetchTake(array $savedValues): array
    {
        $ids = array_keys($savedValues);
        $exercises = self::fetchContents($ids);
        $takeExercises = [];
        reset($savedValues);
        foreach ($exercises as $exerciseId => $content) {
            $takeParts = [];
            $savedValue = current($savedValues);
            if($savedValue === false){
                $savedValue = null;
            }

            while (($part = Utils::arrayShift($content->content)) !== null) {
                if ($part instanceof InternalTypes\TextInput) {
                    $txtI = TextInput::create();
                    if (is_string($savedValue)) {
                        $txtI->setText($savedValue);
                    }
                    $takeParts[] = $txtI;
                } else if ($part instanceof InternalTypes\Combobox) {
                    $cmb = Combobox::create()
                        ->setValues($part->values);
                    if (is_int($savedValue)) {
                        $cmb->selectedIndex = $savedValue;
                    }
                    $takeParts[] = $cmb;
                } else if (is_string($part)) {
                    $takeParts[] = $part;
                    continue;
                } else {
                    $partType = get_debug_type($part);
                    throw new InternalException(
                        "Unsupported content part type '$partType'.",
                        context: [
                            'partType' => $partType,
                            'part' => $part,
                            'content' => $content
                        ]
                    );
                }

                if ($savedValue !== null) {
                    $savedValue = next($savedValues);
                    if($savedValue === false){
                        $savedValue = null;
                    }
                }
            }
            unset($content);

            $takeExercise =  new TakeFillInBlanksExercise(
                FillInBlanksFillInBlanksTakeResponse::create()
                    ->setContent($takeParts)
            );
            $takeExercises[$exerciseId] = $takeExercise;
        }
        return $takeExercises;
    }

    public function fetchEvaluate(array $ids): array
    {
        $exercises = self::fetchContents($ids);
        $reviewExercises = [];
        foreach ($exercises as $exerciseId => $content) {
            $reviewExercise =  new EvaluateFillInBlanksExercise($content);
            $reviewExercises[$exerciseId] = $reviewExercise;
        }
        return $reviewExercises;
    }

    public function fetchSave(array $ids): array
    {
        return [];
    }

    public function getCreateHelper(): CCreateExerciseHelper
    {
        return $this->createHelper ??= new CreateFillInBlanksExercise();
    }

    public function delete(array $ids): void
    {
        DB::table(FillInBlanksConstants::TABLE_NAME)
        ->whereIn(FillInBlanksConstants::COL_EXERCISEABLE_ID,$ids)
        ->delete();
    }
}
