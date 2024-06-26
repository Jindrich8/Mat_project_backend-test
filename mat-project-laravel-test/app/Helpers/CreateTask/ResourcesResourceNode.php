<?php

namespace App\Helpers\CreateTask {

    use App\Exceptions\XMLInvalidElementValueException;
    use App\Exceptions\XMLMissingRequiredElementsException;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XML\XMLContextBase;
    use App\Types\XML\XMLNodeBaseWParentNode;

    class ResourcesResourceNode extends XMLNodeBaseWParentNode
    {

        public static function create(GroupResourcesNode $parent): ResourcesResourceNode
        {
            return new self($parent);
        }

        private function __construct(GroupResourcesNode $parent){
            $config = TaskSrcConfig::get();
            parent::__construct(
                name: $config->resourcesResource->name,
                parent: $parent,
                maxCount: $config->maxResourceCountInResources
            );
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            parent::validateStart($attributes,$context,$name);
            $taskRes = $context->getTaskRes();
            $taskRes->addResourceToCurrentGroup();
        }



    public function appendValue(string $value, XMLContextBase $context): void
    {
        $taskRes = $context->getTaskRes();
       $resource = $taskRes->getLastResourceOfCurrentGroup();
       $resource->content = ($resource->content ?? "").$value;
    }

        /**
         * @param XMLContextBase $context
         * @throws XMLInvalidElementValueException
         * @throws XMLMissingRequiredElementsException
         */
        protected function validate(XMLContextBase $context): void
    {
        parent::validate($context);
        $resource = $context->getTaskRes()->getLastResourceOfCurrentGroup();
        $element = TaskSrcConfig::get()->resourcesResource;
        $resourceContent = $resource->content;
        $error = $element->validateWLength($resourceContent,$length);
        $resource->content = $resourceContent;
        if($error){
            $this->invalidValue(
            description:$error
        );
        }
    }
}
}
