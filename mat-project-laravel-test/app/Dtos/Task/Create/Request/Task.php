<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Create\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class Task extends ClassStructure
{
    /** @var int[]|array */
    public $tags;

    /** @var string */
    public $source;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->tags = Schema::arr();
        $properties->tags->items = Schema::integer();
        $properties->tags->minItems = 1;
        $properties->tags->uniqueItems = true;
        $properties->source = Schema::string();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->required = array(
            self::names()->source,
            self::names()->tags,
        );
    }

    /**
     * @param int[]|array $tags
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string $source
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}