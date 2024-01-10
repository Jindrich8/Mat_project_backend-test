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
 * XML: Missing required elements error data
 * Serves as error action specific data.
 */
class XMLMissingRequiredElementsErrorData extends ClassStructure
{
    /** @var int Column of element with missing required children */
    public $eColumn;

    /** @var int Line of element with missing required children */
    public $eLine;

    /** @var int Byte index of element with missing required children */
    public $eByteIndex;

    /** @var string[]|array Missing required elements */
    public $missingElements;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->eColumn = Schema::integer();
        $properties->eColumn->title = "Element column";
        $properties->eColumn->description = "Column of element with missing required children";
        $properties->eColumn->minimum = 0;
        $properties->eLine = Schema::integer();
        $properties->eLine->title = "Element line";
        $properties->eLine->description = "Line of element with missing required children";
        $properties->eLine->minimum = 0;
        $properties->eByteIndex = Schema::integer();
        $properties->eByteIndex->title = "Element byte index";
        $properties->eByteIndex->description = "Byte index of element with missing required children";
        $properties->eByteIndex->minimum = 0;
        $properties->missingElements = Schema::arr();
        $properties->missingElements->items = Schema::string();
        $properties->missingElements->title = "Missing elements";
        $properties->missingElements->description = "Missing required elements";
        $properties->missingElements->minItems = 1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "XML: Missing required elements error data";
        $ownerSchema->description = "Serves as error action specific data.";
        $ownerSchema->required = array(
            self::names()->eColumn,
            self::names()->eLine,
            self::names()->eByteIndex,
            self::names()->missingElements,
        );
    }

    /**
     * @param int $eColumn Column of element with missing required children
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setEColumn($eColumn)
    {
        $this->eColumn = $eColumn;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $eLine Line of element with missing required children
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setELine($eLine)
    {
        $this->eLine = $eLine;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $eByteIndex Byte index of element with missing required children
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setEByteIndex($eByteIndex)
    {
        $this->eByteIndex = $eByteIndex;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string[]|array $missingElements Missing required elements
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setMissingElements($missingElements)
    {
        $this->missingElements = $missingElements;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}