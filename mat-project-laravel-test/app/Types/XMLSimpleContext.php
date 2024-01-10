<?php

namespace App\Types {

    use App\Exceptions\InternalException;
    use App\Helpers\CreateTask\TaskRes;

    class XMLSimpleContext extends XMLContextBase
    {
        private TaskRes $taskRes;
        private GetXMLParserPosition $getPos;


        public function __construct(TaskRes $taskRes, GetXMLParserPosition $getPos){
            $this->taskRes = $taskRes;
            $this->getPos = $getPos;
        }

        public function getTaskRes(): TaskRes
        {
            return $this->taskRes;
        }

        public function getPos(?int &$column, ?int &$line, ?int &$byteIndex): void
        {
          $this->getPos->getPos($column, $line, $byteIndex);
        }
    }
}