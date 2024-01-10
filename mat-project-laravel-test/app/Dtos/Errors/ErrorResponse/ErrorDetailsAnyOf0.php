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
 * Endpoint specific error details
 */
class ErrorDetailsAnyOf0 extends ClassStructure
{
    /** @var int Serves as identifier for action which should be triggered by app. */
    public $code;

    /** @var array Serves as error action specific data. */
    public $errorData;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->code = Schema::integer();
        $properties->code->title = "Endpoint specific error code";
        $properties->code->description = "Serves as identifier for action which should be triggered by app.";
        $properties->errorData = (new Schema())->setType([Schema::OBJECT, Schema::_ARRAY]);
        $properties->errorData->title = "Error data";
        $properties->errorData->description = "Serves as error action specific data.";
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->description = "Endpoint specific error details";
        $ownerSchema->required = array(
            self::names()->code,
        );
    }

    /**
     * @param int $code Serves as identifier for action which should be triggered by app.
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
     * @param array $errorData Serves as error action specific data.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setErrorData($errorData)
    {
        $this->errorData = $errorData;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}