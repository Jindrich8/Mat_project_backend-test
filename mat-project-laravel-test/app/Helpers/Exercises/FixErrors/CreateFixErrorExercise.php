<?php

namespace App\Helpers\Exercises\FixErrors;

use App\Dtos\InternalTypes\FixErrorsContent\FixErrorsContent;
use App\Exceptions\InternalException;
use App\Helpers\CCreateExerciseHelper;
use App\Models\FixErrors;
use App\Types\CCreateExerciseHelperState;
use App\Types\XMLDynamicNodeBase;
use App\Types\XMLNodeBase;
use DB;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

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
    }

    

    public function getContentNode(string $name,XMLNodeBase $parent): XMLNodeBase
    {
        $content = FixErrorsContent::create();
        $this->contents[]=$content;
        $this->createNode ??= FixErrorsXMLCreateNode::create();
        $this->createNode->setContent($content);
        $this->createNode->change($parent,$name);
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
            $exportedContent = FixErrorsContent::export($this->contents[$i]);
            $data[]=[
                FixErrors::ID => $ids[$i],
                FixErrors::CORRECT_TEXT =>$exportedContent['correctText'],
                FixErrors::WRONG_TEXT =>$exportedContent['wrongText']
            ];
        }
       if(!FixErrors::insert($data)){
        throw new InternalException(
            "Failed to insert Fill in blanks contents to database",
        context:['data'=>$data]
    );
       }
    }
}