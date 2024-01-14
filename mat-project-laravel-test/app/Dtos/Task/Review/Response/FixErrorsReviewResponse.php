<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Review\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FixErrors/review_response.json
 */
class FixErrorsReviewResponse extends ClassStructure
{
    /** @var string */
    public $exerType;

    /** @var FixErrorsReviewResponseContent */
    public $content;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->exerType = "HledaniChyb";
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->exerType = Schema::string();
        $properties->exerType->const = "HledaniChyb";
        $properties->content = FixErrorsReviewResponseContent::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema";
        $ownerSchema->required = array(
            self::names()->exerType,
            self::names()->content,
        );
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FixErrors/review_response.json');
    }

    /**
     * @param string $exerType
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setExerType($exerType)
    {
        $this->exerType = $exerType;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param FixErrorsReviewResponseContent $content
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setContent(FixErrorsReviewResponseContent $content)
    {
        $this->content = $content;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}