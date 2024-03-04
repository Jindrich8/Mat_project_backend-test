<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\Dtos\Defs\Exercises\FixErrors\Action;
    use App\Dtos\Defs\Exercises\FixErrors\FixErrorsEvaluateRequest;
    use App\Dtos\Defs\Exercises\FixErrors\FixErrorsReviewResponse;
    use App\Dtos\Defs\Types\Review\ExercisePoints;
    use App\Dtos\Defs\Types\Review\ExerciseReview;
    use App\Exceptions\InvalidEvaluateValueException;
    use App\Helpers\CEvaluateExercise;
    use App\Utils\DtoUtils;
    use App\Utils\StrUtils;
    use Fisharebest\Algorithm\MyersDiff;
    use Illuminate\Support\Facades\Log;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class EvaluateFixErrorsExercise implements CEvaluateExercise
    {
        /**
         * @var string|string[] $correctText
         */
        private string|array $correctText;
        private string|array $defaultText;
        private int $defaultDistance;

        public function __construct(string $correctText, string $defaultText, int $defaultDistance)
        {
            $this->correctText = $correctText;
            $this->defaultDistance = $defaultDistance;
            $this->defaultText = $defaultText;
        }

        /**
         * @throws InvalidEvaluateValueException
         */
        public function evaluateAndSetAsContentTo(ClassStructure $value, ExerciseReview $exercise): void
        {
            if (!($value instanceof FixErrorsEvaluateRequest)) {
                throw new InvalidEvaluateValueException();
            }

            $response = FixErrorsReviewResponse::create();
            $ops =  &$response->content;
            $distance = null;
            $value = $value->content;
            if ($value === null) {
                if (!is_array($this->defaultText)) {
                    $this->defaultText = StrUtils::getChars($this->defaultText);
                }
                $value = $this->defaultText;
            } else {
                $value = StrUtils::getChars($value);
            }
            $this->correctText = is_array($this->correctText) ?
                $this->correctText
                : StrUtils::getChars($this->correctText);

            $correctChars = $this->correctText;


            $calculated = null;
            $distance = 0; {
                $myers = new MyersDiff();
                /**
                 * @var array{0:string,1:int}[] $calculated
                 */
                $calculated = $myers->calculate($value, $correctChars);
                unset($myers);
            }
            Log::info(self::class . " calculated", [
                'correctText' => implode("", $correctChars),
                'userText' => implode("", $value),
                'calculated' => $calculated
            ]);
            if ($calculated) {
                $str = "";
                $action = $calculated[0][1];
                while (($op = array_shift($calculated))) {
                    [$ch, $opAction] = $op;
                    if ($action !== MyersDiff::KEEP) {
                        ++$distance;
                    }
                    if ($opAction === $action) {
                        $str .= $ch;
                    } else {
                        $responseOp = $str;
                        $resAction = match ($action) {
                            MyersDiff::DELETE => Action::DEL,
                            MyersDiff::INSERT => Action::INS,
                            default => null
                        };
                        if ($resAction !== null) {
                            $responseOp = Action::create()
                                ->setAction($resAction)
                                ->setValue($str);
                        }

                        $ops[] = $responseOp;
                        $str = $ch;
                        $action = $opAction;
                    }
                }
                if ($str) {
                    $responseOp = $str;
                    $resAction = match ($action) {
                        MyersDiff::DELETE => Action::DEL,
                        MyersDiff::INSERT => Action::INS,
                        default => null
                    };
                    if ($resAction !== null) {
                        $responseOp = Action::create()
                            ->setAction($resAction)
                            ->setValue($str);
                    }

                    $ops[] = $responseOp;
                }
            }

            Log::info("Evaluate FixErrors", ['distance' => $distance, 'defaultDistance' => $this->defaultDistance, 'ops' => $ops]);
            $has = $this->defaultDistance - $distance;
            if ($has < 0) {
                $has = 0;
            }
            $exercise->setPoints(
                ExercisePoints::create()
                    ->setHas($has)
                    ->setMax($this->defaultDistance)
            )
                ->setDetails($response);

            Log::info("exporting " . self::class . "", ['response' => $response]);
            DtoUtils::exportDto($response);
        }
    }
}
