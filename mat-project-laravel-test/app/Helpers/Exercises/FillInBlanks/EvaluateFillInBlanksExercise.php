<?php

namespace App\Helpers\Exercises\FillInBlanks {

    use App\Dtos\Defs\Exercises\FillInBlanks\DefsCmb;
    use App\Dtos\Defs\Exercises\FillInBlanks\DefsTxtI;
    use App\Dtos\Defs\Exercises\FillInBlanks\FillInBlanksEvaluateRequest;
    use App\Dtos\Defs\Exercises\FillInBlanks\FillInBlanksReviewResponse;
    use App\Dtos\Defs\Types\Review\ExercisePoints;
    use App\Dtos\Defs\Types\Review\ExerciseReview;
    use App\Dtos\InternalTypes\FillInBlanksContent;
    use App\Exceptions\ApplicationException;
    use App\Helpers\CEvaluateExercise;
    use App\Dtos\InternalTypes\TextInput;
    use App\Dtos\InternalTypes\Combobox;
    use App\Exceptions\InvalidEvaluateValueException;
    use App\Utils\DebugUtils;
    use App\Utils\DtoUtils;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class EvaluateFillInBlanksExercise implements CEvaluateExercise
    {
        private FillInBlanksContent $content;

        public function __construct(FillInBlanksContent $content)
        {
            $this->content = $content;
        }

        /**
         * @throws ApplicationException
         * @throws InvalidEvaluateValueException
         */
        public function evaluateAndSetAsContentTo(ClassStructure $value, ExerciseReview $exercise): void
        {
            if (!($value instanceof FillInBlanksEvaluateRequest)) {
                throw new InvalidEvaluateValueException();
            }
            $points = 0;
            $data = $value;
            $uiI = 0;

            $response = FillInBlanksReviewResponse::create()
                ->setContent([]);
            /**
             * @var TextInput|Combobox|string $item
             */
            foreach ($this->content->content as $item) {
                $responseItem = null;
                if (!is_string($item)) {
                    $filled = $data->content[$uiI++] ?? null;
                    if ($item instanceof TextInput) {
                        $responseItem = DefsTxtI::create()
                            ->setUserValue($filled);
                        if ($filled === null) {
                            $responseItem->setCorrectValue($item->correctText);
                        } else if ($filled === $item->correctText) {
                            ++$points;
                        } else {
                            $responseItem->setCorrectValue($item->correctText);
                        }
                    } else {
                        $filledValue = $item->values[$filled] ?? null;
                        $responseItem = DefsCmb::create()
                            ->setUserValue($filledValue);
                        if ($filledValue === null) {
                            $responseItem->setCorrectValue($item->values[$item->selectedIndex]);
                        } else if ($filled === $item->selectedIndex) {
                            ++$points;
                        } else {
                            $responseItem->setCorrectValue($item->values[$item->selectedIndex]);
                        }
                    }
                } else {
                    $responseItem = $item;
                }
                $response->content[] = $responseItem;
            }
            $exercise->setPoints(
                ExercisePoints::create()
                    ->setHas($points)
                    ->setMax($uiI)
            )
                ->setDetails($response);
            DebugUtils::log("exporting " . self::class . "", ['response' => $response]);
            DtoUtils::exportDto($response);
        }
    }
}
