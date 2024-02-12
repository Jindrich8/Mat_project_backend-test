<?php

namespace App\Types {

    use App\Dtos\InternalTypes\TaskSaveContent;

    class SaveTask implements SavedTaskContentProvider
    {
        public function __construct(
            public readonly int $taskVersion,
            public readonly TaskSaveContent $content
        )
        {
            
        }

        public function getContent(): TaskSaveContent
        {
            return $this->content;
        }
    }
}