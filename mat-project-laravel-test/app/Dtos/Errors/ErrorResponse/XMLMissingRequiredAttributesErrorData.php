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
 * XML: Missing required attributes error data
 * Serves as error action specific data.
 */
class XMLMissingRequiredAttributesErrorData extends ClassStructure
{
    /** @var int Column of element with missing attributes */
    public $eColumn;

    /** @var int Line of element with missing attributes */
    public $eLine;

    /** @var int Byte index of element with missing attributes */
    public $eByteIndex;

    /** @var string[]|array Missing required attributes */
    public $missingAttributes;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->eColumn = Schema::integer();
        $properties->eColumn->title = "Element column";
        $properties->eColumn->description = "Column of element with missing attributes";
        $properties->eColumn->minimum = 0;
        $properties->eLine = Schema::integer();
        $properties->eLine->title = "Element line";
        $properties->eLine->description = "Line of element with missing attributes";
        $properties->eLine->minimum = 0;
        $properties->eByteIndex = Schema::integer();
        $properties->eByteIndex->title = "Element byte index";
        $properties->eByteIndex->description = "Byte index of element with missing attributes";
        $properties->eByteIndex->minimum = 0;
        $properties->missingAttributes = Schema::arr();
        $properties->missingAttributes->items = Schema::string();
        $properties->missingAttributes->title = "Missing attributes";
        $properties->missingAttributes->description = "Missing required attributes";
        $properties->missingAttributes->minItems = 1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "XML: Missing required attributes error data";
        $ownerSchema->description = "Serves as error action specific data.";
        $ownerSchema->required = array(
            self::names()->eColumn,
            self::names()->eLine,
            self::names()->eByteIndex,
            self::names()->missingAttributes,
        );
    }

    /**
     * @param int $eColumn Column of element with missing attributes
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
     * @param int $eLine Line of element with missing attributes
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
     * @param int $eByteIndex Byte index of element with missing attributes
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
     * @param string[]|array $missingAttributes Missing required attributes
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setMissingAttributes($missingAttributes)
    {
        $this->missingAttributes = $missingAttributes;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}