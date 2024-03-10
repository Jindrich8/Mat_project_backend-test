<?php

namespace App\Helpers\Exercises\FillInBlanks;

use App\Dtos\InternalTypes\FillInBlanksContent;
use App\Exceptions\InternalException;
use App\Helpers\CCreateExerciseHelper;
use App\ModelConstants\FillInBlanksConstants;
use App\Models\FillInBlanks;
use App\Types\CCreateExerciseHelperStateEnum;
use App\Types\XML\XMLDynamicNodeBase;
use App\Types\XML\XMLNodeBase;
use App\Utils\DebugLogger;
use App\Utils\DtoUtils;
use DB;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Structure\ClassStructure;

class CreateFillInBlanksExercise implements CCreateExerciseHelper
{
    private ?FillInBlanksXMLCreateNode $createNode;
    /**
     * @var FillInBlanksContent[] $contents
     */
    private array $contents;


    public function __construct()
    {
        $this->createNode = null;
        $this->contents = [];
    }

    public function reset(): void
    {
        $this->createNode = null;
        $this->contents = [];
    }

    public function getContentNode(string $name,XMLNodeBase $parent): XMLDynamicNodeBase
    {
        DebugLogger::debug("getFillInBlanksContentNode (".count($this->contents).") for '$name'",['parent'=>$parent]);
        $content = FillInBlanksContent::create();
        $this->contents[]=$content;
        $this->createNode ??= FillInBlanksXMLCreateNode::create();
        /*
        The order of change and setContent methods is importnat,
         because change calls reset, which sets the content to null*/
        $this->createNode->change($parent,$name);
        $this->createNode->setContent($content);
        return $this->createNode;

    }

    public function getState(): CCreateExerciseHelperStateEnum
    {
       return $this->createNode->getParsingState();
    }

    /**
     * @throws InvalidValue
     */
    public function insertAll(array $ids): void
    {
        $count = count($ids);
        $contentCount = count($this->contents);
        if($count !== $contentCount){
            throw new InternalException("There should be same count of ids '$count' as contents '$contentCount'",
            context:[
                'contents'=>$this->contents,
            'ids'=>$ids
        ]);
        }
        $data = [];
        for($i = 0; $i < $count; ++$i){
            $content = $this->contents[$i];
            if(!($content instanceof ClassStructure)){
                throw new InternalException("ARE YOU KIDDING ME!!???\nWHAT THE HACK IS GOING ON!!??!???",
            context:[
                'contents'=>$this->contents,
            ]);
            }
            $data[]=[
                FillInBlanksConstants::COL_EXERCISEABLE_ID => $ids[$i],
                FillInBlanksConstants::COL_CONTENT => DtoUtils::dtoToJson($content,FillInBlanksContent::CONTENT)
            ];
        }
        $success = DB::table(FillInBlanks::getTableName())
                                ->insert($data);
       if(!$success){
        throw new InternalException(
            "Failed to insert Fill in blanks contents to database",
        context:['data'=>$data]
    );
       }
    }
}
