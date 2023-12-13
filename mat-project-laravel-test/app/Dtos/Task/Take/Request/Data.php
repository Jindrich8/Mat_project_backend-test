<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Take\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class Data extends ClassStructure
{
    /** @var DataLocalySavedTask */
    public $localySavedTask;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->localySavedTask = DataLocalySavedTask::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->required = array(
            self::names()->localySavedTask,
        );
    }

    /**
     * @param DataLocalySavedTask $localySavedTask
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setLocalySavedTask(DataLocalySavedTask $localySavedTask)
    {
        $this->localySavedTask = $localySavedTask;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}