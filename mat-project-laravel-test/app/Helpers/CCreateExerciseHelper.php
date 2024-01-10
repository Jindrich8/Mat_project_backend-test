<?php

namespace App\Helpers;

use App\Exceptions\BadSaveExerciseException;
use App\Types\XMLDynamicNodeBase;
use App\Types\XMLNodeBase;
use App\Types\CCreateExerciseHelperState;
use App\Types\Transformable;

interface CCreateExerciseHelper
{

    public function getContentNode(string $name,XMLNodeBase $parent):XMLNodeBase;

    public function getState():CCreateExerciseHelperState;

    /**
     * @param int[] $ids
     */
    public function insertAll(array $ids):void;
}
