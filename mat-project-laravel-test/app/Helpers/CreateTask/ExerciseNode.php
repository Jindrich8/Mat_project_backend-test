<?php

namespace App\Helpers\CreateTask {

    use App\Helpers\CreateTask\Document\DocumentContent;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\XMLAttributes;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Types\XMLNodeBaseWParentNode;
    use App\Types\XMLNodeBase;

    class ExerciseNode extends XMLNodeBaseWParentNode
    {

        public static function create(DocumentContent|GroupMembersNode $parent): self
        {
            $node = new self($parent);
            $node->setChildren(
                XMLChildren::construct()
                ->addChild(ExerciseInstructionsNode::create($node),required:true)
                ->addChild(ExerciseContentNode::create($node),required:true)
            );
            return $node;
        }

        private function __construct(DocumentContent|GroupMembersNode $parent){
            $config = TaskSrcConfig::get();
            parent::__construct(
                name: $config->exerciseName,
                isValueNode:false,
                attributes: XMLAttributes::construct()
                ->addAttribute(
                    name: $config->exerciseTypeAttr->name,
                    parse: function(XMLNodeBase $node, string $value, XMLContextBase $context){
                        $attr = TaskSrcConfig::get()->exerciseTypeAttr;
                       $type = $attr->validate($value);
                       if($type === null){
                        $this->invalidEnumAttributeValue(
                            attribute:$attr->name,
                            allowedValues:$attr->getAllowedEnumStringValues(),
                            getPosCallback:$context
                        );
                       }
                       $context->getTaskRes()->getLastExercise()->exerciseType = $type;
                    },
                    required: true)
                ->addAttribute(
                    name: $config->exerciseWeightAttr->name,
                    parse: function(XMLNodeBase $node, string $value, XMLContextBase $context){
                        $attr = TaskSrcConfig::get()->exerciseWeightAttr;
                       $error = $attr->validate($value,$parsed);
                       if($error){
                        $this->invalidAttributeValue(
                            attribute:$attr->name,
                        description:$error,
                        getPosCallback:$context
                        );
                       }
                       $context->getTaskRes()->getLastExercise()->weight = $parsed;
                    },
                    required: true
                ),
                parent: $parent
        );
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            $taskRes = $context->getTaskRes();
            $maxExerciseCount = TaskSrcConfig::get()->maxExerciseCount;
            $specified = $taskRes->getExerciseCount();
            if($specified >= $maxExerciseCount){
                $this->tooManyElements($context,$maxExerciseCount);
            }
            $taskRes->addExercise();

            parent::validateStart($attributes,$context,$name);
        }
    }
}
