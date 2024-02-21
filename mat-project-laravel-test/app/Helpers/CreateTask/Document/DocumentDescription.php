<?php

namespace App\Helpers\CreateTask\Document {

    use App\Exceptions\XMLInvalidElementValueException;
    use App\Types\XMLNodeBaseWParentNode;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLContextBase;

    class DocumentDescription extends XMLNodeBaseWParentNode{

        public static function create(Document $document){
            $desc = new self($document);
            return $desc;
        }

        private function __construct(Document $document){
            parent::__construct(
                parent: $document,
                name:TaskSrcConfig::get()->taskDescription->name,
                maxCount:1
            );
        }

    public function appendValue(string $value, XMLContextBase $context): void
    {
        $task = $context->getTaskRes()->task;
        $task->description = ($task->description ?? "").$value;
    }

        /**
         * @throws XMLInvalidElementValueException
         */
        protected function validate(XMLContextBase $context): void
    {
        parent::validate($context);

        $task = $context->getTaskRes()->task;
        $validator = TaskSrcConfig::get()->taskDescription;
        $taskDescription  = $task->description;
      $error =  $validator->validateWLength($taskDescription,$length);
        $task->description = $taskDescription;

        if($error!== null){
            $this->invalidValue(
            description:$error
        );
        }
    }
}
}
