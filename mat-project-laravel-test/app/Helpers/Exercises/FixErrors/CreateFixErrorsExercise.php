<?php

namespace App\Helpers\Exercises\FixErrors;

use App\Dtos\InternalTypes\FixErrorsContent;
use App\Exceptions\InternalException;
use App\Helpers\CCreateExerciseHelper;
use App\ModelConstants\FixErrorsConstants;
use App\Models\FixErrors;
use App\Types\CCreateExerciseHelperState;
use App\Types\XMLDynamicNodeBase;
use App\Types\XMLNodeBase;
use App\Utils\DtoUtils;
use App\Utils\StrUtils;
use Fisharebest\Algorithm\MyersDiff;
use Swaggest\JsonSchema\InvalidValue;

class CreateFixErrorsExercise implements CCreateExerciseHelper
{
    private ?FixErrorsXMLCreateNode $createNode;
    /**
     * @var FixErrorsContent[] $contents
     */
    private array $contents;


    public function __construct()
    {
        $this->createNode = null;
        $this->contents = [];
    }



    public function getContentNode(string $name,XMLNodeBase $parent): XMLDynamicNodeBase
    {
        $content = FixErrorsContent::create();
        $this->contents[]=$content;
        $this->createNode ??= FixErrorsXMLCreateNode::create();
        /*
        The order of change and setContent methods is important,
         because change calls reset, which sets the content to null*/
         $this->createNode->change($parent,$name);
        $this->createNode->setContent($content);
        return $this->createNode;
    }

    public function getState(): CCreateExerciseHelperState
    {
        return $this->createNode->getParsingState();
    }

    /**
     * @param array $ids
     * @throws \Exception
     */
    public function insertAll(array $ids): void
    {
        $count = count($ids);
        if($count !== count($this->contents)){
            throw new InternalException("There should be same count of ids as contents",
            context:[
                'contents'=>$this->contents,
            'ids'=>$ids
        ]);
        }
        $data = [];
        for($i = 0; $i < $count; ++$i){
            $content = $this->contents[$i];
            $distance = 0;
            $myers = new MyersDiff();
            // TODO: change this to calculate only distance between texts, there is no need to calculate whole edit sequence
            $calculated = $myers->calculate(StrUtils::getChars($content->wrongText),StrUtils::getChars($content->correctText));
            while(($op = array_shift($calculated)) !== null){
                [$ch, $opAction] = $op;
                if($opAction !== MyersDiff::KEEP){
                    ++$distance;
                }
            }
            $exportedContent = DtoUtils::exportDto($content);
            $data[]=[
                FixErrorsConstants::COL_EXERCISEABLE_ID => $ids[$i],
                FixErrorsConstants::COL_CORRECT_TEXT => DtoUtils::accessExportedField($exportedContent,FixErrorsContent::CORRECT_TEXT),
                FixErrorsConstants::COL_WRONG_TEXT =>DtoUtils::accessExportedField($exportedContent,FixErrorsContent::WRONG_TEXT),
                FixErrorsConstants::COL_DISTANCE => $distance
            ];
        }
       if(!FixErrors::insert($data)){
        throw new InternalException(
            "Failed to insert Fill in blanks contents to database",
        context:['data'=>$data]
    );
       }
    }

    public function reset(): void
    {
        $this->createNode = null;
        $this->contents = [];
    }
}
