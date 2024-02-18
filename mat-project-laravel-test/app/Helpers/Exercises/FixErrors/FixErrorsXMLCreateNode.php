<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\Dtos\InternalTypes\FixErrorsContent;
    use App\Exceptions\InternalException;
    use App\Types\XMLChildren;
    use App\Types\XMLContextBase;
    use App\Types\XMLDynamicNodeBase;
    use App\Types\XMLNodeBase;
    use App\Types\XMLNodeValueType;
    use App\Types\XMLNoValueNodeTrait;
    use App\Types\CCreateExerciseHelperState as ParsingState;

    final class FixErrorsXMLCreateNode extends XMLDynamicNodeBase
    {
        use XMLNoValueNodeTrait;
        private ?FixErrorsContent $content;
        private ?XMLNodeBase $parent;
        private ParsingState $state;

        public static function create(){
            $node = new self();
            $node->setChildren(XMLChildren::construct()
            ->addChild(CorrectTextNode::create($node))
            ->addChild(WrongTextNode::create($node))
        );
        return $node;
        }

        public function getContent():FixErrorsContent{
            if (!$this->content) {
                throw new InternalException(
                    "Fix errors content should not be null",
                    context: ['create node' => $this]
                );
            }
            return $this->content;
        }

        public function setContent(FixErrorsContent $content) { 
            $this->content = $content;
        }

        public function getParsingState(): ParsingState{ 
            return $this->state;
        }

        public function getParentObjectId(): ?object
        {
            return $this->parent;
        }

        protected function getParentName(): ?string
        {
            return $this->parent?->getName();
        }

        protected function __construct(){
            parent::__construct("FixErrorsCreateNode",
        maxCount:1);
        $this->parent = null;
        $this->content = null;
        $this->state = ParsingState::EXERCISE_ENDED;
        }

        public function reset()
        {
            parent::reset();
            $this->content = null;
            $this->state = ParsingState::EXERCISE_ENDED;
        }
        

        public function change(XMLNodeBase $newParent, string $newName): void
        {
            $this->parent = $newParent;
            $this->name = $newName;
            $this->content = null;
        }

        protected function moveUp(XMLContextBase $context): ?XMLNodeBase
        {
            return $this->parent;
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            parent::validateStart($attributes,$context,$name);
            $this->getContent();
            $this->state = ParsingState::STARTED_EXERCISE_HAS_CONTENT;
        }
       
       protected function validate(XMLContextBase $context): void
       {
        parent::validate($context);
        $this->state = ParsingState::EXERCISE_ENDED;
       }
    }
}