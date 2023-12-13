<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Create\Request;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class DataTask extends ClassStructure
{
    const HORIZONTAL = 'Horizontal';

    const VERTICAL = 'Vertical';

    /** @var string */
    public $name;

    /** @var mixed */
    public $orientation;

    /** @var string */
    public $description;

    /** @var string */
    public $source;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->name = Schema::string();
        $properties->name->maxLength = 60;
        $properties->name->minLength = 5;
        $properties->orientation = new Schema();
        $properties->orientation->enum = array(
            self::HORIZONTAL,
            self::VERTICAL,
        );
        $properties->description = Schema::string();
        $properties->description->maxLength = 500;
        $properties->source = Schema::string();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->additionalProperties = false;
        $ownerSchema->required = array(
            self::names()->name,
            self::names()->orientation,
            self::names()->description,
            self::names()->source,
        );
    }

    /**
     * @param string $name
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param mixed $orientation
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string $description
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setDescription($description)
    {
        $this->description = $description;
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