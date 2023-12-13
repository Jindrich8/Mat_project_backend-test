<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\ErrorResponse;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Application error object
 * Serves as object specifying details about the error for both application and user.
 */
class ApplicationErrorObject extends ClassStructure
{
    /** @var string Human readable error message */
    public $message;

    /** @var int Serves as identifier for action which should be triggered by app. */
    public $code;

    /** @var array Serves as error action specific data. */
    public $errorData;

    /** @var string Human readable details about error or help message, specifying which action should user take */
    public $description;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->message = Schema::string();
        $properties->message->title = "Human readable error message";
        $properties->message->description = "Human readable error message";
        $properties->code = Schema::integer();
        $properties->code->title = "Endpoint specific error code";
        $properties->code->description = "Serves as identifier for action which should be triggered by app.";
        $properties->errorData = (new Schema())->setType([Schema::OBJECT, Schema::_ARRAY]);
        $properties->errorData->title = "Error data";
        $properties->errorData->description = "Serves as error action specific data.";
        $properties->description = Schema::string();
        $properties->description->title = "Human readable error description";
        $properties->description->description = "Human readable details about error or help message, specifying which action should user take";
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "Application error object";
        $ownerSchema->description = "Serves as object specifying details about the error for both application and user.";
        $ownerSchema->required = array(
            self::names()->type,
            self::names()->message,
            self::names()->code,
            self::names()->description,
        );
    }

    /**
     * @param string $message Human readable error message
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

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

    /**
     * @param string $description Human readable details about error or help message, specifying which action should user take
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}