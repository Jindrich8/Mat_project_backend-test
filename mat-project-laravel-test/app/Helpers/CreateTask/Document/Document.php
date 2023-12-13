<?php

namespace App\Helpers\CreateTask\Document {

    use App\Exceptions\XMLInvalidElementException;
    use App\Helpers\CreateTask\TaskRes;
    use App\Helpers\CreateTask\XMLNodeBase;
    use App\Helpers\CreateTask\XMLNoValueNode;
    use App\Helpers\CreateTask\XMLOneUseNode;
    use App\TableSpecificData\TaskDisplay;
    use Illuminate\Support\Str;

    class Document extends XMLNodeBase
    {

       public function __construct()
       {
        parent::__construct(
            parent:null,
            name:"document",
            children:[
                new XMLOneUseNode(
                    'description',
                parent:$this,
                appendValue:function(XMLOneUseNode $thisNode,string $value,TaskRes $taskRes){
                    if($taskRes->task->description){
                        $taskRes->task->description .= $value;
                    }
                    else{
                        $taskRes->task->description = $value;
                    }
                },
                validateStart:function(XMLOneUseNode $thisNode,iterable $attributes,TaskRes $taskRes,?string $name){
                    if($taskRes->task->description){
                        throw null;
                        //throw new XMLInvalidElementException();
                    }
                }
            ),
            new XMLOneUseNode(
                'content',
                parent:$this,
                validateStart:function(XMLOneUseNode $thisNode,iterable $attributes,TaskRes $taskRes,?string $name){
                    if($taskRes->exercises || $taskRes->groups){
                        throw null;
                        //throw new XMLInvalidElementException();
                    }
                },
                children:[

                ]
                
            )
            ]
            );
       }

           /**
         * @return array{string,callable(string,TaskRes):void}
         */
        protected function getRequiredAttributes():array{
            return [
                'name' => function(string $attributeValue,TaskRes $taskRes) {
                    $taskRes->task->name = $attributeValue;
                    return $attributeValue;
                },
                'orientation' => function(string $attributeValue,TaskRes $taskRes) {
                  $attributeValue =  Str::lower($attributeValue);
                 $orientation = TaskDisplay::tryFrom($attributeValue);
                 if(!$orientation){
                    $this->invalidEnumAttributeValue(
                        'orientation',
                    TaskDisplay::getValues()
                );
            }
                $taskRes->task->orientation = $orientation->value;
        }
            ];
        }

        function getNonRequiredAttributes(): array
        {
            return [];
        }


       function appendValue(string $value, TaskRes $taskRes, callable $getParserPosition): void
       {
        $this->valueNotSupported();
       }

        function validate(TaskRes $taskRes): void
        {
            
        }
        
    }
}