<?php

namespace App\Helpers;

interface CExerciseHelper
{
    /**
     *
     * @param int[] $ids
     * @return array<int,CTakeExercise> array indexed by id
     */
    public function fetchTake(array $ids): array;

    /**
     * @param int[] $ids
     * @return array<int,CSaveExercise> array indexed by id
     */
    public function fetchSave(array $ids):array;

   
    public function getCreateHelper():CCreateExerciseHelper;

}
