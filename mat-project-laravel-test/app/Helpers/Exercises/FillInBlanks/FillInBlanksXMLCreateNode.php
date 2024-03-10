<?php

namespace App\Helpers\Exercises\FillInBlanks {

    use App\Dtos\InternalTypes\FillInBlanksContent;
    use App\Types\XML\XMLContextBase;
    use App\Types\XML\XMLNodeBase;
    use App\Types\CCreateExerciseHelperStateEnum as ParsingState;

    final class FillInBlanksXMLCreateNode extends Parser
    {
        private ?XMLNodeBase $parent;

        private ParsingState $state;


        public function setContent(FillInBlanksContent $content)
        {
            $this->content = $content->setContent([]);
        }

        public static function create()
        {
            $node = new self();
            return $node;
        }

        public function getParsingState(): ParsingState
        {
            return $this->state;
        }

        protected function __construct()
        {
            parent::__construct("FillInBlanksCreateNode", maxCount: 1);
            $this->reset();
        }

        public function reset(): void
        {
            parent::reset();
            $this->state = ParsingState::EXERCISE_ENDED;
            $this->parent = null;
        }


        public function change(XMLNodeBase $newParent, string $newName): void
        {
            $this->reset();
            $this->parent = $newParent;
            $this->name = $newName;
        }

        public function getParentName(): ?string
        {
            return $this->parent?->getName();
        }

        public function getParentObjectId(): ?object
        {
            return $this->parent;
        }

        public function moveUp(XMLContextBase $context): ?XMLNodeBase
        {
            return $this->parent;
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            parent::validateStart($attributes, $context, $name);
            $this->getContent();
            $this->state = ParsingState::STARTED_EXERCISE_HAS_CONTENT;
        }

        protected function validate(XMLContextBase $context): void
        {
            parent::validate($context);
            $this->state = ParsingState::EXERCISE_ENDED;
            $this->reset();
        }
    }
}
