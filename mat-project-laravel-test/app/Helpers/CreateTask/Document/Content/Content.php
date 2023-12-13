<?php

namespace App\Helpers\CreateTask\Document\Content {

    use App\Helpers\CreateTask\TaskRes;
    use App\Helpers\CreateTask\XMLNoAttributesNode;
    use App\Helpers\CreateTask\Document;
    use App\Utils\Utils;

    class DocumentContent extends XMLNoAttributesNode
    {

        public static function create(Document\Document $parent){
            self::createInternal(parent:$parent);
        }

       function init(?string &$name, ?array &$children): ?string
       {
            $name = "description";
            $children=[];
            return Document\Document::class;
       }

        function appendValue(string $value,TaskRes $taskRes): void
        {
            Utils::ifTrueAppendElseSet($taskRes->task->description,$value);
        }

        function validateValue(TaskRes $taskRes): void
        {
            $taskRes->task->description ??="";
        }
    }
}