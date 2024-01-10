<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Errors\ErrorResponse;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Application error response
 */
class ErrorResponse extends ClassStructure
{
    /** @var ApplicationErrorObject Serves as object specifying details about the error for both application and user. */
    public $error;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->error = ApplicationErrorObject::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Application error response";
    }

    /**
     * @param ApplicationErrorObject $error Serves as object specifying details about the error for both application and user.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setError(ApplicationErrorObject $error)
    {
        $this->error = $error;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}