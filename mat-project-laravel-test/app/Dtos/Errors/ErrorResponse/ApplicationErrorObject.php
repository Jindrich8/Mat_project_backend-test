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
 * Application error object
 * Serves as object specifying details about the error for both application and user.
 */
class ApplicationErrorObject extends ClassStructure
{
    /** @var string Human readable error message */
    public $message;

    /** @var ErrorDetailsAnyOf0|XMLInvalidAttribute|XMLInvalidAttributeValue|XMLInvalidElement|XMLInvalidElementValue|XMLInvalidElementValuePart|XMLMissingRequiredAttributes|XMLMissingRequiredElements|XMLSyntaxError|XMLUnsupportedConstruct */
    public $details;

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
        $properties->details = Schema::object();
        $properties->details->anyOf[0] = ErrorDetailsAnyOf0::schema();
        $properties->details->anyOf[1] = XMLInvalidAttribute::schema();
        $properties->details->anyOf[2] = XMLInvalidAttributeValue::schema();
        $properties->details->anyOf[3] = XMLInvalidElement::schema();
        $properties->details->anyOf[4] = XMLInvalidElementValue::schema();
        $properties->details->anyOf[5] = XMLInvalidElementValuePart::schema();
        $properties->details->anyOf[6] = XMLMissingRequiredAttributes::schema();
        $properties->details->anyOf[7] = XMLMissingRequiredElements::schema();
        $properties->details->anyOf[8] = XMLSyntaxError::schema();
        $properties->details->anyOf[9] = XMLUnsupportedConstruct::schema();
        $properties->details->title = "Error details";
        $properties->details->required = array(
            self::names()->code,
            self::names()->errorData,
        );
        $properties->description = Schema::string();
        $properties->description->title = "Human readable error description";
        $properties->description->description = "Human readable details about error or help message, specifying which action should user take";
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "Application error object";
        $ownerSchema->description = "Serves as object specifying details about the error for both application and user.";
        $ownerSchema->required = array(
            self::names()->message,
            self::names()->details,
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
     * @param ErrorDetailsAnyOf0|XMLInvalidAttribute|XMLInvalidAttributeValue|XMLInvalidElement|XMLInvalidElementValue|XMLInvalidElementValuePart|XMLMissingRequiredAttributes|XMLMissingRequiredElements|XMLSyntaxError|XMLUnsupportedConstruct $details
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setDetails($details)
    {
        $this->details = $details;
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