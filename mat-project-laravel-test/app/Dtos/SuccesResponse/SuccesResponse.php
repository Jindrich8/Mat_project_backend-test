<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\SuccesResponse;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Application success response
 * Specifies format of successfull response.
 */
class SuccesResponse extends ClassStructure
{
    /** @var mixed Specifies endpoint and request specific response data */
    public $data;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->data = Schema::object();
        $properties->data->title = "Response data";
        $properties->data->description = "Specifies endpoint and request specific response data";
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Application success response";
        $ownerSchema->description = "Specifies format of successfull response.";
        $ownerSchema->required = array(
            self::names()->data,
        );
    }

    /**
     * @param mixed $data Specifies endpoint and request specific response data
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