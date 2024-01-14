<?php

namespace App\Helpers\Exercises\FillInBlanks;

use App\Dtos\InternalTypes\FillInBlanksContent;
use App\Dtos\InternalTypes\FillInBlanksContent\FillInBlanksContent as FillInBlanksContentFillInBlanksContent;
use App\Dtos\Task\Take\Response\FillInBlanksTakeResponse;
use App\Helpers\CCreateExerciseHelper;
use App\Helpers\CExerciseHelper;
use App\Helpers\Database\DBJsonHelper;
use App\Models\FillInBlanks;
use App\Utils\StrUtils;
use App\Dtos\Task\Take;
use App\Exceptions\InternalException;
use App\Utils\Utils;
use DB;

class FillInBlanksExerciseHelper implements CExerciseHelper
{
    private ?CreateFillInBlanksExercise $createHelper;

    public function __construct(){
        $this->createHelper = null;
    }

    public function fetchTake(array $ids,array $savedValues): array
    {
        $table = FillInBlanks::getTableName();
        $idName = FillInBlanks::getPrimaryKeyName();
       $exercises = DB::table($table)
        ->select([$idName,FillInBlanks::CONTENT])
        ->whereIn($idName,$ids)
        ->get();
        unset($ids);
        $takeExercises = [];
        while(($exercise = $exercises->pop()) !== null){
            $decodedContent = DBJsonHelper::decode(
                json:$exercise[FillInBlanks::CONTENT],
            table:$table,
            column:FillInBlanks::CONTENT,
            id:$exercise[$idName]
            );
        
           $content = FillInBlanksContent\FillInBlanksContent::import([
                'structure'=>$decodedContent
            ]);
            $takeParts = [];
            $savedValue = null;
            $getNextSavedValue = true;
            while(($part = Utils::arrayShift($content->structure)) !== null){
                if($getNextSavedValue && $savedValues){
                    $savedValue = Utils::arrayShift($savedValues);
                }
                $getNextSavedValue = true;
                if($part instanceof FillInBlanksContent\TextInput){
                    $txtI = Take\Response\TextInput::create();
                    if(is_string($savedValue)){
                        $txtI->setText($savedValue);
                    }
                    $takeParts[]= $txtI;
                }
                else if($part instanceof FillInBlanksContent\Combobox){
                    $cmb = Take\Response\Combobox::create()
                    ->setValues($part->values);
                    if(is_int($savedValue)){
                        $cmb->selectedIndex = $savedValue;
                    }
                    $takeParts[]= $cmb;
                }
                else if(is_string($part)){
                    $getNextSavedValue = false;
                    $takeParts[]= $part;
                }
                else{
                    $partType = get_debug_type($part);
                    throw new InternalException("Unsupported content part type '$partType'.",
                    context:[
                        'partType'=>$partType,
                        'part'=>$part,
                        'exercise'=>$exercise
                    ]);
                }
            }
            unset($content);

          $takeExercise =  new TakeFillInBlanksExercise(FillInBlanksTakeResponse::create()
            ->setContent($takeParts));
            $takeExercises[$exercise[$idName]]=$takeExercise;
        }
        return $takeExercises;
    }

    public function fetchSave(array $ids): array
    {
        return [];
    }

    public function getCreateHelper(): CCreateExerciseHelper
    {
        return $this->createHelper ??= new CreateFillInBlanksExercise();
    }
}
