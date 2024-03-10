<?php

namespace App\Types {

    use App\Dtos\InternalTypes\TaskSaveContent;

    interface SavedTaskContentProviderInterface
    {
        public function getContent():TaskSaveContent;
    }
}