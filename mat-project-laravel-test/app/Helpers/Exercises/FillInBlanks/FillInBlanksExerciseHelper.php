<?php

namespace App\Helpers\Exercises\FillInBlanks;

use App\Dtos\Defs\Exercises\FillInBlanks\FillInBlanksTakeResponse as FillInBlanksFillInBlanksTakeResponse;
use App\Dtos\InternalTypes\FillInBlanksContent;
use App\Dtos\InternalTypes;
use App\Helpers\CCreateExerciseHelper;
use App\Helpers\CExerciseHelper;
use App\Dtos\Defs\Exercises\FillInBlanks\Combobox;
use App\Dtos\Defs\Exercises\FillInBlanks\TextInput;
use App\Dtos\InternalTypes\Combobox as InternalTypesCombobox;
use App\Dtos\InternalTypes\TextInput as InternalTypesTextInput;
use App\Exceptions\InternalException;
use App\Helpers\Database\DBHelper;
use App\Helpers\Database\DBJsonHelper;
use App\ModelConstants\FillInBlanksConstants;
use App\Types\StopWatchTimer;
use App\Utils\DebugLogger;
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
            $cmbsAndTxtInputsArray = DBJsonHelper::decode(
                json: DBHelper::access($exercise, FillInBlanksConstants::COL_CONTENT),
                table: $table,
                column: FillInBlanksConstants::COL_CONTENT,
                id: $exerciseId
            );
            if (!is_array($cmbsAndTxtInputsArray)) {
                throw new InternalException(
                    message: "FillInBlanksContent is not an array",
                    context: [
                        'content' => $cmbsAndTxtInputsArray
                    ]
                );
            }
            $importedArray = [];
            foreach ($cmbsAndTxtInputsArray as $strOrCmbOrTxtI) {
                $item = null;
                if (is_string($strOrCmbOrTxtI)) {
                    $item = $strOrCmbOrTxtI;
                } else {
                    $cmbType = DBHelper::tryToAccess($strOrCmbOrTxtI, InternalTypesCombobox::TYPE);
                    if ($cmbType === InternalTypesCombobox::TYPE_CONST) {
                        $values = DBHelper::access($strOrCmbOrTxtI, InternalTypesCombobox::VALUES);
                        if (!is_array($values)) {
                            throw new InternalException("Combobox is missing values!", [
                                'combobox' => $strOrCmbOrTxtI
                            ]);
                        }
                        $selectedIndex =DBHelper::tryToAccess($strOrCmbOrTxtI, InternalTypesCombobox::SELECTED_INDEX);
                        $item = InternalTypesCombobox::create()
                            ->setSelectedIndex($selectedIndex)
                            ->setValues($values);
                    } else {
                        $txtIType = DBHelper::tryToAccess($strOrCmbOrTxtI, InternalTypesTextInput::TYPE);
                        if ($txtIType !== InternalTypesTextInput::TYPE_CONST) {
                            throw new InternalException("Unsupported fillable component!", [
                                'component' => $strOrCmbOrTxtI
                            ]);
                        }
                        $correctText = DBHelper::access($strOrCmbOrTxtI, InternalTypesTextInput::CORRECT_TEXT);
                        if (!is_string($correctText)) {
                            throw new InternalException(
                                message: "Field of txt input '" . InternalTypesTextInput::CORRECT_TEXT . "' must be a string.",
                                context: [
                                    'txtInput' => $strOrCmbOrTxtI
                                ]
                            );
                        }
                        $item = InternalTypesTextInput::create()
                            ->setCorrectText($correctText);
                    }
                }
                $importedArray[] = $item;
            }
            
            $content = FillInBlanksContent::create()
                ->setContent($importedArray);
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
            $exerciseResp = FillInBlanksFillInBlanksTakeResponse::create()
                ->setContent([]);

            $takeParts = &$exerciseResp->content;
            $savedValue = current($savedValues);
            if ($savedValue === false) {
                $savedValue = null;
            }
            $content = $content->content;
            /**
             * @var InternalTypes\TextInput|InternalTypes\Combobox|string $part
             */
            while (($part = array_shift($content)) !== null) {
                if (is_string($part)) {
                    $takeParts[] = $part;
                    continue;
                } else if ($part->type === InternalTypes\TextInput::TYPE_CONST) {
                    $txtI = TextInput::create();
                    if (is_string($savedValue)) {
                        $txtI->setText($savedValue);
                    }
                    $takeParts[] = $txtI;
                } else if ($part->type === InternalTypes\Combobox::TYPE_CONST) {
                    $cmb = Combobox::create()
                        ->setValues($part->values);
                    if (is_int($savedValue)) {
                        $cmb->selectedIndex = $savedValue;
                    }
                    $takeParts[] = $cmb;
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
                    if ($savedValue === false) {
                        $savedValue = null;
                    }
                }
            }
            unset($content);

            $takeExercises[$exerciseId] = new TakeFillInBlanksExercise($exerciseResp);
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
            ->whereIn(FillInBlanksConstants::COL_EXERCISEABLE_ID, $ids)
            ->delete();
    }
}
