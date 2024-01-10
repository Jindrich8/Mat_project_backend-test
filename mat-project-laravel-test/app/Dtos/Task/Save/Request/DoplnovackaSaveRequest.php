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
 * Doplnovacka save request
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/Doplnovacka/save_request.json
 */
class DoplnovackaSaveRequest extends ClassStructure
{
    /** @var int[]|string[]|array */
    public $content;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->content = Schema::arr();
        $properties->content->items = new Schema();
        $propertiesContentItemsOneOf0 = Schema::integer();
        $propertiesContentItemsOneOf0->minimum = 0;
        $properties->content->items->oneOf[0] = $propertiesContentItemsOneOf0;
        $properties->content->items->oneOf[1] = Schema::string();
        $properties->content->minItems = 1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Doplnovacka save request";
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/Doplnovacka/save_request.json');
    }

    /**
     * @param int[]|string[]|array $content
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