<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Save\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class Data extends ClassStructure
{
    /** @var FillInBlanksSaveRequest[]|HledaniChybSaveRequest[]|array */
    public $exercises;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->exercises = Schema::arr();
        $properties->exercises->items = new Schema();
        $properties->exercises->items->anyOf[0] = FillInBlanksSaveRequest::schema();
        $properties->exercises->items->anyOf[1] = HledaniChybSaveRequest::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->required = array(
            self::names()->exercises,
        );
    }

    /**
     * @param FillInBlanksSaveRequest[]|HledaniChybSaveRequest[]|array $exercises
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setExercises($exercises)
    {
        $this->exercises = $exercises;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}