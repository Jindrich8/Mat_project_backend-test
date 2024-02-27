<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\Dtos\Defs\Exercises\FixErrors\DeleteAction;
    use App\Dtos\Defs\Exercises\FixErrors\FixErrorsEvaluateRequest;
    use App\Dtos\Defs\Exercises\FixErrors\FixErrorsReviewResponse;
    use App\Dtos\Defs\Exercises\FixErrors\InsertAction;
    use App\Dtos\Defs\Types\Review\ExercisePoints;
    use App\Dtos\Defs\Types\Review\ExerciseReview;
    use App\Exceptions\InvalidEvaluateValueException;
    use App\Helpers\CEvaluateExercise;
    use App\Utils\StrUtils;
    use Fisharebest\Algorithm\MyersDiff;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class EvaluateFixErrorsExercise implements CEvaluateExercise
    {
        /**
         * @var string|string[] $correctText
         */
        private string|array $correctText;

        public function __construct(string $correctText)
        {
            $this->correctText = $correctText;
        }

        /**
         * @throws InvalidEvaluateValueException
         */
        public function evaluateAndSetAsContentTo(ClassStructure $value, ExerciseReview $exercise): void
        {
            if(!($value instanceof FixErrorsEvaluateRequest)){
                throw new InvalidEvaluateValueException();
            }
            $response = FixErrorsReviewResponse::create();
            $ops =  &$response->content;
            $distance = null;
            $maxDistance = null;
            $value = $value->content;
            if ($value) {
                $this->correctText = is_array($this->correctText) ?
                    $this->correctText
                    : StrUtils::getChars($this->correctText);

                $correctChars = $this->correctText;
                $value = StrUtils::getChars($value);

                $calculated = null;
                /**
                 * @var int $maxDistance
                 */
                $maxDistance = count($correctChars) + count($value);
                $distance = 0; {
                    $myers = new MyersDiff();
                    /**
                     * @var array{0:string,1:int}[] $calculated
                     */
                    $calculated = $myers->calculate($correctChars, $value);
                    unset($myers);
                }
                if ($calculated) {
                    $str = "";
                    $action = $calculated[0][1];
                    while (($op = array_shift($calculated))) {
                        [$ch, $opAction] = $op;
                        if ($opAction === $action) {
                            $str .= $ch;
                        } else {
                            if ($action !== MyersDiff::KEEP) {
                                ++$distance;
                            }
                            $ops[] = match ($action) {
                                MyersDiff::DELETE => DeleteAction::create()->setDEL($str),
                                MyersDiff::INSERT => InsertAction::create()->setINS($str),
                                default => $str
                            };
                            $str = "";
                            $action = $opAction;
                        }
                    }
                }
            } else {
                
                $correctTextStr = null;
                $maxDistance = null;
                if(is_array($this->correctText)){
                    $correctTextStr = implode("",$this->correctText);
                    $maxDistance = count($this->correctText);
                }
                else{
                    $correctTextStr = $this->correctText;
                    $maxDistance = StrUtils::length($this->correctText);
                }

                $distance = $maxDistance;
                $ops = [InsertAction::create()->setINS($correctTextStr)];
            }

            $exercise->setPoints(
                ExercisePoints::create()
                    ->setHas($maxDistance - $distance)
                    ->setMax($maxDistance)
            )
                ->setDetails($response);
        }
    }
}
