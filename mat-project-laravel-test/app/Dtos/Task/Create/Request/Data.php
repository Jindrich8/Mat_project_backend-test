<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Create\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class Data extends ClassStructure
{
    /** @var DataTask */
    public $task;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->task = DataTask::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->required = array(
            self::names()->task,
        );
    }

    /**
     * @param DataTask $task
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setTask(DataTask $task)
    {
        $this->task = $task;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}