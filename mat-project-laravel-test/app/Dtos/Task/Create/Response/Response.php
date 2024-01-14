<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Create\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Task create response
 */
class Response extends ClassStructure
{
    /** @var int */
    public $taskId;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->taskId = Schema::integer();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Task create response";
        $ownerSchema->required = array(
            self::names()->taskId,
        );
    }

    /**
     * @param int $taskId
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}