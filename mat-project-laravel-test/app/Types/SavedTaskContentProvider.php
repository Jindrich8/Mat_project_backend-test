<?php

namespace App\Types {

    use App\Dtos\InternalTypes\TaskSaveContent;

    interface SavedTaskContentProvider
    {
        public function getContent():TaskSaveContent;
    }
}