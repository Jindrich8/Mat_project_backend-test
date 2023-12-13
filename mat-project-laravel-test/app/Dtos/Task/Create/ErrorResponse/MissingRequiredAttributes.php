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
 * Missing required attributes
 */
class MissingRequiredAttributes extends ClassStructure
{
    /** @var mixed Serves as identifier for action which should be triggered by app. */
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
        $properties->code->title = "Endpoint specific error code";
        $properties->code->description = "Serves as identifier for action which should be triggered by app.";
        $properties->code->const = 4;
        $properties->errorData = ErrorData::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "Missing required attributes";
    }

    /**
     * @param mixed $code Serves as identifier for action which should be triggered by app.
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