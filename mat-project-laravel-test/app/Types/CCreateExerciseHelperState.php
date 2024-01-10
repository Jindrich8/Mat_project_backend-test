<?php

namespace App\Types {

    enum CCreateExerciseHelperState:int
    {
        case STARTED_EXERCISE_HAS_CONTENT = 0;
        case EXERCISE_ENDED = 1;

    }
}