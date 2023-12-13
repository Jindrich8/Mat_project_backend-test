<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Create\ErrorResponse;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Invalid attribute
 */
class InvalidAttribute extends ClassStructure
{
    /** @var mixed */
    public $code;

    /** @var ErrorAllOf1OneOf1ErrorData */
    public $errorData;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->code = new Schema();
        $properties->code->const = 2;
        $properties->errorData = ErrorAllOf1OneOf1ErrorData::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "Invalid attribute";
    }

    /**
     * @param mixed $code
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param ErrorAllOf1OneOf1ErrorData $errorData
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setErrorData(ErrorAllOf1OneOf1ErrorData $errorData)
    {
        $this->errorData = $errorData;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}