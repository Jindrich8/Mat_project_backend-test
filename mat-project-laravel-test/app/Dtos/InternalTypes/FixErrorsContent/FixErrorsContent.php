<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\InternalTypes\FixErrorsContent;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Fix errors's content structure
 * Represents the content structure of Fix errors exercise.
 */
class FixErrorsContent extends ClassStructure
{
    /** @var string Represents correct text to which should be wrongText corrected. */
    public $correctText;

    /** @var string Represents starting text with errors, that should be corrected to match correctText */
    public $wrongText;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->correctText = Schema::string();
        $properties->correctText->title = "Correct text";
        $properties->correctText->description = "Represents correct text to which should be wrongText corrected.";
        $properties->correctText->minLength = 1;
        $properties->wrongText = Schema::string();
        $properties->wrongText->title = "Wrong starting text";
        $properties->wrongText->description = "Represents starting text with errors, that should be corrected to match correctText";
        $properties->wrongText->minLength = 1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Fix errors's content structure";
        $ownerSchema->description = "Represents the content structure of Fix errors exercise.";
        $ownerSchema->required = array(
            self::names()->correctText,
            self::names()->wrongText,
        );
    }

    /**
     * @param string $correctText Represents correct text to which should be wrongText corrected.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setCorrectText($correctText)
    {
        $this->correctText = $correctText;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string $wrongText Represents starting text with errors, that should be corrected to match correctText
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setWrongText($wrongText)
    {
        $this->wrongText = $wrongText;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}