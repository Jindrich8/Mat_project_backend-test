<?php

namespace App\Types {

    use App\Helpers\CreateTask\TaskRes;

    class XMLContext extends XMLContextBase
    {
        private TaskRes $taskRes;
        private BaseXMLParser $xmlParser;

        public function __construct(BaseXMLParser $xmlParser,TaskRes $taskRes){
            $this->taskRes = $taskRes;
            $this->xmlParser = $xmlParser;
        }

        public function getTaskRes(): TaskRes
        {
            return $this->taskRes;
        }

        public function setBaseXMLParser(BaseXMLParser $parser):void{
            $this->xmlParser = $parser;
        }

        public function getPos(?int &$column, ?int &$line, ?int &$byteIndex): void
        {
            $this->xmlParser->getPos(
                column:$column,
                line:$line,
                byteIndex:$byteIndex
            );
        }
        
    }
}