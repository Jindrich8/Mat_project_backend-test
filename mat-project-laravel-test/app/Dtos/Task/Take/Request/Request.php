<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Take\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Take task request
 */
class Request extends ClassStructure
{
    /** @var LocalySavedTask */
    public $localySavedTask;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->localySavedTask = LocalySavedTask::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema";
        $ownerSchema->title = "Take task request";
        $ownerSchema->required = array(
            self::names()->localySavedTask,
        );
    }

    /**
     * @param LocalySavedTask $localySavedTask
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setLocalySavedTask(LocalySavedTask $localySavedTask)
    {
        $this->localySavedTask = $localySavedTask;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}