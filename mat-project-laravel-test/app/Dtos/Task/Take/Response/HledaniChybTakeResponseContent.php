<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Take\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class HledaniChybTakeResponseContent extends ClassStructure
{
    /** @var string */
    public $defaultText;

    /** @var string */
    public $text;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->defaultText = Schema::string();
        $properties->text = Schema::string();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->defaultText,
        );
    }

    /**
     * @param string $defaultText
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setDefaultText($defaultText)
    {
        $this->defaultText = $defaultText;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string $text
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}