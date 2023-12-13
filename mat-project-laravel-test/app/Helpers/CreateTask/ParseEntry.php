<?php

namespace App\Helpers\CreateTask\ParseEntry {

    use App\Exceptions\XMLInvalidElementException;
    use App\Exceptions\XMLParsingException;
    use App\Helpers\CreateTask\TaskRes;
    use App\Helpers\CreateTask\XMLNodeBase;
    use App\Models\Group;
    use App\Types\XMLParserPosition;
    use finfo;
    use Illuminate\Support\Facades\DB;
    use XMLParser;

    enum XMLParserEntryType{
        case ELEMENT_START;
        case ELEMENT_END;
        case ELEMENT_VALUE;
        case UNSUPPORTED_ENTITY;
    }

    abstract class ParseEntry
    {
        private XMLNodeBase $node;
        private TaskRes $taskRes;
        private XMLParserPosition $lastPos = new XMLParserPosition();
        private ?XMLParserEntryType $prevEntryType = null;



        public function __construct()
        {
            
        }

        /**
         * @param XMLParserEntryType $entryType - type of current entity
         * @return XMLParserEntryType|null - the type of previous entity
         */
        private function initHandler(XMLParserEntryType $entryType):?XMLParserEntryType{
            $prevEntryType = $this->prevEntryType;
            $this->prevEntryType = $entryType;
            return $prevEntryType;
        }

        private function unsupportedConstructHandler(XMLParser $parser, string $data): void
        {
            $this->initHandler(XMLParserEntryType::UNSUPPORTED_ENTITY);
            $this->lastPos->updateFromParser($parser);

           // throw new XMLInvalidElementException();
           throw null;
        }

        private function elementStartHandler(XMLParser $parser, string $name, array $attributes): void
        {
          $prevEntryType =  $this->initHandler(XMLParserEntryType::ELEMENT_START);
            
          $this->lastPos->updateFromParser($parser);

            if ($prevEntryType) {
                // TODO: repair
                $this->node = $this->node->getChild($name,fn()=>$this->lastPos);
            }

            $this->node->validateStart(
                name: $name,
                taskRes: $this->taskRes,
                attributes: $attributes
            );
        }

        private function elementEndHandler(XMLParser $parser, string $name): void
        {
            $this->initHandler(XMLParserEntryType::ELEMENT_END);

            $this->lastPos->updateFromParser($parser);

            $this->node->validate($this->taskRes, $name);
        }

        private function elementValueHandler(XMLParser $parser, string $data): void
        {
            $this->initHandler(XMLParserEntryType::ELEMENT_VALUE);

            $this->lastPos->updateFromParser($parser);

            $this->node->appendValue($data, $this->taskRes,fn()=>$this->lastPos);
        }

        /**
         * @param iterable<string> $data
         * @param string $encoding
         * @return bool
         */
        public function parse(iterable $data, string $encoding = "UTF-8"): void
        {
            try {
                // Prepare error buffer
                libxml_clear_errors();
                libxml_use_internal_errors(true);

                // Create parser
                $parser = xml_parser_create($encoding);

                // Set options
                xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
                xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, true);

                // Set handlers
                xml_set_default_handler($parser, $this->unsupportedConstructHandler(...));
                xml_set_element_handler($parser, $this->elementStartHandler(...), $this->elementEndHandler(...));
                xml_set_character_data_handler($parser, $this->elementValueHandler(...));

                // Parse
                foreach ($data as $dataPart) {
                    xml_parse($parser, data: $dataPart);
                }
                xml_parse($parser, data: "", is_final: true);

            } finally {
                // Free parser
                xml_parser_free($parser);
            }
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                // TODO
            }
        }
    }
}
