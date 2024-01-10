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
 * XML: Unsupported construct
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/errors/XML/UnsupportedConstruct.json
 */
class XMLUnsupportedConstruct extends ClassStructure
{
    /** @var int Serves as identifier for action which should be triggered by app. */
    public $code;

    /** @var XMLUnsupportedConstructErrorData Serves as error action specific data. */
    public $errorData;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->code = 107;
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->code = Schema::integer();
        $properties->code->title = "Endpoint specific error code";
        $properties->code->description = "Serves as identifier for action which should be triggered by app.";
        $properties->code->const = 107;
        $properties->errorData = XMLUnsupportedConstructErrorData::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "XML: Unsupported construct";
        $ownerSchema->required = array(
            self::names()->code,
            self::names()->errorData,
        );
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/errors/XML/UnsupportedConstruct.json');
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
     * @param XMLUnsupportedConstructErrorData $errorData Serves as error action specific data.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setErrorData(XMLUnsupportedConstructErrorData $errorData)
    {
        $this->errorData = $errorData;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}