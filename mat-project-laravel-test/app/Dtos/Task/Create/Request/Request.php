<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Create\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Task create request
 */
class Request extends ClassStructure
{
    /** @var Task */
    public $task;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->task = Task::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Task create request";
        $ownerSchema->required = array(
            self::names()->data,
        );
    }

    /**
     * @param Task $task
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setTask(Task $task)
    {
        $this->task = $task;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}