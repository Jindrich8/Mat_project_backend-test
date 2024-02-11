<?php

namespace App\Helpers\Exercises\FillInBlanks {

    use App\Dtos\Defs\Exercises\FillInBlanks\FillInBlanksEvaluateRequest;
    use App\Dtos\Defs\Exercises\FillInBlanks\FillInBlanksReviewResponse;
    use App\Dtos\Defs\Types\Review\ExercisePoints;
    use App\Dtos\InternalTypes\FillInBlanksContent;
    use App\Dtos\TaskInfo\Review\DefsExercise;
    use App\Dtos\TaskInfo\Review\Get\DefsExercise as GetDefsExercise;
    use App\Helpers\CEvaluateExercise;
    use App\Helpers\RequestHelper;
    use App\Dtos\InternalTypes\TextInput;
    use App\Dtos\InternalTypes\Combobox;
    use App\Dtos\InternalTypes\DefsCmb;
    use App\Dtos\InternalTypes\DefsTxtI;

    class EvaluateFillInBlanksExercise implements CEvaluateExercise
    {
        private FillInBlanksContent $content;

        public function __construct(FillInBlanksContent $content){
            $this->content = $content;
        }

        public function evaluateAndSetAsContentTo(mixed $value, GetDefsExercise $exercise): void
        {
            $points = 0;
           $data = RequestHelper::requestDataToDto(FillInBlanksEvaluateRequest::class,$value);
           $uiI = 0;

          $response = FillInBlanksReviewResponse::create()
          ->setContent([]);
           /**
            * @var TextInput|Combobox|string $item
            */
           foreach($this->content->content as $item){
            if(!is_string($item)){
               $responseItem =null;
               $filled = $data->content[$uiI++];
               if($item instanceof TextInput){
                $responseItem = DefsTxtI::create()
                ->setUserValue($filled);
                if($filled === $item->correctText){
                    ++$points;
                }
                else{
                    $responseItem->setCorrectValue($item->correctText);
                }
               }
               else{
                $responseItem = DefsCmb::create()
                ->setUserValue($filled);
                if($filled === $item->selectedIndex){
                    ++$points;
                }
                else{
                    $responseItem->setCorrectValue($item->selectedIndex);
                }
               }
               $response->content[]=$responseItem;
            }
           }
           $exercise->setPoints(
            ExercisePoints::create()
           ->setHas($points)
           ->setMax($uiI)
           )
           ->setDetails($response);
        }
    }
}