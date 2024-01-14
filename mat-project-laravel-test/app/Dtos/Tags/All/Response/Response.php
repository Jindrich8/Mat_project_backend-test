<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Tags\All\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Get all tags response
 */
class Response extends ClassStructure
{
    /** @var Tag[]|array */
    public $tags;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->tags = Schema::arr();
        $properties->tags->items = Tag::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Get all tags response";
        $ownerSchema->required = array(
            self::names()->tags,
        );
    }

    /**
     * @param Tag[]|array $tags
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}