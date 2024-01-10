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
 * XML: Invalid element value part
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/errors/XML/InvalidElementValuePart.json
 */
class XMLInvalidElementValuePart extends ClassStructure
{
    /** @var int */
    public $code;

    /** @var XMLInvalidElementValuePartErrorData Serves as error action specific data. */
    public $errorData;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->code = 104;
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->code = Schema::integer();
        $properties->code->const = 104;
        $properties->errorData = XMLInvalidElementValuePartErrorData::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "XML: Invalid element value part";
        $ownerSchema->required = array(
            self::names()->code,
            self::names()->errorData,
        );
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/errors/XML/InvalidElementValuePart.json');
    }

    /**
     * @param int $code
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
     * @param XMLInvalidElementValuePartErrorData $errorData Serves as error action specific data.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setErrorData(XMLInvalidElementValuePartErrorData $errorData)
    {
        $this->errorData = $errorData;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}