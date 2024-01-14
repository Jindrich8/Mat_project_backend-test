<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Save\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * HledaniChyb save request
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FixErrors/save_request.json
 */
class HledaniChybSaveRequest extends ClassStructure
{
    /** @var string */
    public $content;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->content = Schema::string();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "HledaniChyb save request";
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FixErrors/save_request.json');
    }

    /**
     * @param string $content
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}