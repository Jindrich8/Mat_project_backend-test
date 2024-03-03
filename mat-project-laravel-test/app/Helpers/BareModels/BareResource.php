<?php

namespace App\Helpers\BareModels {

    class BareResource
    {
        public ?string $content;

        public function __construct(){
            $this->content = null;
        }
    }
}