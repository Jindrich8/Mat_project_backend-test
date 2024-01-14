<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Review\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class FixErrorsReviewResponseContent extends ClassStructure
{
    /** @var string */
    public $userText;

    /** @var string */
    public $correctText;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->userText = Schema::string();
        $properties->correctText = Schema::string();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->userText,
        );
    }

    /**
     * @param string $userText
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setUserText($userText)
    {
        $this->userText = $userText;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string $correctText
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setCorrectText($correctText)
    {
        $this->correctText = $correctText;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}