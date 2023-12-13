<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Evaluate\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Evaluate task request
 */
class Request extends ClassStructure
{
    /** @var Data */
    public $data;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->data = Data::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema";
        $ownerSchema->title = "Evaluate task request";
        $ownerSchema->required = array(
            self::names()->data,
        );
    }

    /**
     * @param Data $data
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setData(Data $data)
    {
        $this->data = $data;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}