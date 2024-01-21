<?php

namespace App\Helpers\Exercises\FillInBlanks;

use App\Dtos\InternalTypes\FillInBlanksContent\FillInBlanksContent;
use App\Exceptions\InternalException;
use App\Helpers\CCreateExerciseHelper;
use App\Models\FillInBlanks;
use App\Types\CCreateExerciseHelperState;
use App\Types\XMLDynamicNodeBase;
use App\Types\XMLNodeBase;
use App\Utils\DtoUtils;
use App\Utils\StrUtils;
use DB;
use Doctrine\DBAL\Query\QueryBuilder;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Structure\ClassStructure;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

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
    }

    public function getContentNode(string $name,XMLNodeBase $parent): XMLNodeBase
    {
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

    public function getState(): CCreateExerciseHelperState
    {
       return $this->createNode->getParsingState();
    }

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
            if(!($content instanceof ClassStructure)){
                throw new InternalException("ARE YOU KIDDING ME!!???\nWHAT THE HACK IS GOING ON!!??!???",
            context:[
                'contents'=>$this->contents,
            ]);
            }
            //echo "Content: ";
           // dump($content);
            $data[]=[
                FillInBlanks::ID => $ids[$i],
                FillInBlanks::CONTENT => DtoUtils::dtoToJson($content,FillInBlanksContent::CONTENT)
            ];
        }
        echo "Data: ";
        dump($data);
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
