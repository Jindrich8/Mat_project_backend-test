<?php

namespace App\Helpers;

use App\Types\XML\XMLDynamicNodeBase;
use App\Types\XML\XMLNodeBase;
use App\Types\CCreateExerciseHelperStateEnum;

interface CCreateExerciseHelper
{

    public function getContentNode(string $name,XMLNodeBase $parent):XMLDynamicNodeBase;

    public function getState():CCreateExerciseHelperStateEnum;

    /**
     * @param int[] $ids
     */
    public function insertAll(array $ids):void;

    public function reset():void;
}
