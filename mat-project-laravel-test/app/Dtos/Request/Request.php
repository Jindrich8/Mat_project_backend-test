<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Request
 * Specifies format of the request body.
 */
class Request extends ClassStructure
{
    /** @var mixed Endpoint specific request data. */
    public $data;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->data = Schema::object();
        $properties->data->title = "Request data";
        $properties->data->description = "Endpoint specific request data.";
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Request";
        $ownerSchema->description = "Specifies format of the request body.";
        $ownerSchema->required = array(
            self::names()->data,
        );
    }

    /**
     * @param mixed $data Endpoint specific request data.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}