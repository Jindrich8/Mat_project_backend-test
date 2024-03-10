<?php

namespace App\Helpers;

use App\Types\XMLDynamicNodeBase;
use App\Types\XMLNodeBase;
use App\Types\CCreateExerciseHelperState;

interface CCreateExerciseHelper
{

    public function getContentNode(string $name,XMLNodeBase $parent):XMLDynamicNodeBase;

    public function getState():CCreateExerciseHelperState;

    /**
     * @param int[] $ids
     */
    public function insertAll(array $ids):void;

    public function reset():void;
}
