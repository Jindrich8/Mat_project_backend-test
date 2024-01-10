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
 * XML: Invalid element
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/errors/XML/InvalidElement.json
 */
class XMLInvalidElement extends ClassStructure
{
    /** @var int */
    public $code;

    /** @var XMLInvalidElementErrorData */
    public $errorData;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->code = 100;
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->code = Schema::integer();
        $properties->code->const = 100;
        $properties->errorData = XMLInvalidElementErrorData::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "XML: Invalid element";
        $ownerSchema->required = array(
            self::names()->code,
            self::names()->errorData,
        );
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/errors/XML/InvalidElement.json');
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
     * @param XMLInvalidElementErrorData $errorData
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setErrorData(XMLInvalidElementErrorData $errorData)
    {
        $this->errorData = $errorData;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}