<?php

namespace App\Helpers;

interface CExerciseHelper
{
    /**
     *
     * @param array<int,mixed> $savedValues indexed by exercise id
     * @return array<int,CTakeExercise> array indexed by id
     */
    public function fetchTake(array $savedValues): array;

    /**
     * @param int[] $ids
     * @return array<int,CSaveExercise> array indexed by id
     */
    public function fetchSave(array $ids):array;

    /**
     * @param int[] $ids
     * @return array<int,CEvaluateExercise> array indexed by id
     */
    public function fetchEvaluate(array $ids):array;

   
    public function getCreateHelper():CCreateExerciseHelper;

}
