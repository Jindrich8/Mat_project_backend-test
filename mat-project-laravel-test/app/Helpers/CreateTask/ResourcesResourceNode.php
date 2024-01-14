<?php

namespace App\Helpers\CreateTask {

    use App\MyConfigs\TaskSrcConfig;
    use App\Types\CreatableNodeTrait;
    use App\Types\XMLContextBase;
    use App\Utils\Utils;
    use App\Types\XMLNodeBaseWParentNode;
    use App\Types\XMLNoValueNodeTrait;
    use App\Types\XMLNodeBase;
    use App\Types\XMLNodeValueType;
    use ValueError;

    class ResourcesResourceNode extends XMLNodeBaseWParentNode
    {

        public static function create(GroupResourcesNode $parent){
            $node = new self($parent);
            return $node;
        }

        private function __construct(GroupResourcesNode $parent){
            $config = TaskSrcConfig::get();
            parent::__construct(
                parent:$parent,
                maxCount:$config->maxResourceCountInResources,
            name:$config->resourcesResource->name
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