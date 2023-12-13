<?php

namespace App\Helpers\CreateTask{

    abstract class XMLNoAttributesNode extends XMLNode{

       protected function getRequiredAttributes(): array
       {
        return [];
       }

       protected function getNonRequiredAttributes(): array
       {
        return [];
       }
    }
}