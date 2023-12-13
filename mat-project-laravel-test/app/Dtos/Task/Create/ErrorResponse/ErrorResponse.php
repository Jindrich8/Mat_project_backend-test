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
 * Application error object
 * Serves as object specifying details about the error for both application and user.
 */
class ErrorResponse extends ClassStructure
{
    /** @var ApplicationErrorObject|InvalidElement|InvalidAttribute|InvalidAttributeValue|MissingRequiredAttributes|InvalidElementValue|MissingRequiredElements */
    public $error;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->error = Schema::object();
        $properties->error->allOf[0] = ApplicationErrorObject::schema();
        $propertiesErrorAllOf1 = new Schema();
        $propertiesErrorAllOf1->oneOf[0] = InvalidElement::schema();
        $propertiesErrorAllOf1->oneOf[1] = InvalidAttribute::schema();
        $propertiesErrorAllOf1->oneOf[2] = InvalidAttributeValue::schema();
        $propertiesErrorAllOf1->oneOf[3] = MissingRequiredAttributes::schema();
        $propertiesErrorAllOf1->oneOf[4] = InvalidElementValue::schema();
        $propertiesErrorAllOf1->oneOf[5] = MissingRequiredElements::schema();
        $properties->error->allOf[1] = $propertiesErrorAllOf1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Application error object";
        $ownerSchema->description = "Serves as object specifying details about the error for both application and user.";
        $ownerSchema->required = array(
            self::names()->errorData,
            self::names()->code,
        );
    }

    /**
     * @param ApplicationErrorObject|InvalidElement|InvalidAttribute|InvalidAttributeValue|MissingRequiredAttributes|InvalidElementValue|MissingRequiredElements $error
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}