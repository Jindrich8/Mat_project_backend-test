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
 * Invalid element value
 */
class InvalidElementValue extends ClassStructure
{
    /** @var mixed */
    public $code;

    /** @var ErrorData Serves as error action specific data. */
    public $errorData;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->code = new Schema();
        $properties->code->const = 5;
        $properties->errorData = ErrorData::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "Invalid element value";
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
     * @param ErrorData $errorData Serves as error action specific data.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setErrorData(ErrorData $errorData)
    {
        $this->errorData = $errorData;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}