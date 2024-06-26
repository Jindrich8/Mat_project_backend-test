<?php

namespace App\Helpers\Exercises\FixErrors {

    use App\Dtos\InternalTypes\FixErrorsContent;
    use App\Exceptions\InternalException;
    use App\Types\XML\XMLChildren;
    use App\Types\XML\XMLContextBase;
    use App\Types\XML\XMLDynamicNodeBase;
    use App\Types\XML\XMLNodeBase;
    use App\Types\CCreateExerciseHelperStateEnum as ParsingState;

    final class FixErrorsXMLCreateNode extends XMLDynamicNodeBase
    {
        private ?FixErrorsContent $content;
        private ?XMLNodeBase $parent;
        private ParsingState $state;

        public static function create(){
            $node = new self();
            $node->setChildren(XMLChildren::construct()
            ->addChild(CorrectTextNode::create($node),required:true)
            ->addChild(WrongTextNode::create($node),required:true)
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
            parent::__construct(
                name:"FixErrorsCreateNode",
        maxCount:1,
        isValueNode:false
    );
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
            $this->reset();
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
