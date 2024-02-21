<?php

namespace App\Types {

    use App\Exceptions\InternalException;
    use App\Exceptions\InvalidArgumentException;
    use App\Utils\DebugUtils;
    use App\Utils\StrUtils;
    use Illuminate\Support\Str;
    use XMLParser;

    enum XMLParserEntryType
    {
        case ELEMENT_START;
        case ELEMENT_END;
        case ELEMENT_VALUE;
        case COMMENT;
        case ERROR;
        case UNSUPPORTED_ENTITY;
    }

    class LibXmlParser implements BaseXMLParser
    {
        private XMLParser $parser;
        private ?XMLParserEntryType $entryType;
        private ?XMLParserEntryType $nextEntryType;
        /**
         * @var string[] $data
         */
        private array $data;
        private int $dataByteIndex;

        /**
         * @var array{line:int,column:int} $pos
         */
        private array $pos;

        /**
         * @var array{line:int,column:int,byteIndex:int} $lastValidPos
         */
        private array $lastValidPos;

        private XMLParserEvents $events;

        private XMLParserPhase $phase;

        private bool $useLibxmlInternalErrors;

        public function setEvents(XMLParserEvents $events): void
        {
            $this->events = $events;
        }

        public static function create(XMLParserEvents $events): static
        {
            return new static($events);
        }

        private function __construct(XMLParserEvents $events)
        {
            /**
             * <element>abcd efg hgg lg</element> <a>
             */
            $this->setValidPosition(
                column: 1,
                line: 1,
                byteIndex: 0
            );
            $this->data = [];
            $this->dataByteIndex = 0;
            $this->entryType = null;
            $this->nextEntryType = null;
            $this->parser = xml_parser_create('UTF-8');
            $this->events = $events;
            $this->phase = XMLParserPhase::NONE;
            $this->useLibxmlInternalErrors = false;

            // Set options
            self::setParserOption($this->parser, XML_OPTION_CASE_FOLDING, false);
            self::setParserOption($this->parser, XML_OPTION_SKIP_WHITE, false);
            xml_set_default_handler($this->parser, $this->otherConstructsHandler(...));
            xml_set_element_handler($this->parser, $this->elementStartHandler(...), $this->elementEndHandler(...));
            xml_set_character_data_handler($this->parser, $this->elementValueHandler(...));

            // Set handlers
            xml_set_notation_decl_handler($this->parser, $this->notationDeclHandler(...));
            xml_set_start_namespace_decl_handler($this->parser, $this->startNamespaceHandler(...));
            xml_set_external_entity_ref_handler($this->parser, $this->externalEntityRefHandlerhandler(...));
            xml_set_unparsed_entity_decl_handler($this->parser, $this->unparsedEntityHandler(...));
            xml_set_processing_instruction_handler($this->parser, $this->processingInstructionHandler(...));
        }

        private static function setParserOption(XMLParser $parser, int $option, $value): void
        {
            if (!xml_parser_set_option($parser, $option, $value)) {
                throw new InvalidArgumentException(
                    argumentName: "parser",
                    argumentValue: $parser,
                    isNotValidBecause: "xml_parser_set_option method marked it as invalid",
                    context: [
                        'option' => $option,
                        'value' => $value
                    ]
                );
            }
        }

        public function parse(string $data, bool $isFinal = false): ?XMLParserError
        {
            // Prepare error buffer
            if ($this->phase === XMLParserPhase::NONE) {
                libxml_clear_errors();
                $this->useLibxmlInternalErrors = libxml_use_internal_errors(true);
            }
            $this->data[] = $data;
            if (!xml_parse($this->parser, $data, $isFinal)) {
                $this->updateEntryType(XMLParserEntryType::ERROR);

                $errorCode = xml_get_error_code($this->parser);
                $this->getLastValidPosition(
                    column: $column,
                    line: $line,
                    byteIndex: $byteIndex
                );
                $this->updatePos();
                $errorStr = "";
                $libxmlError = libxml_get_last_error();
                /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
                if (false !== $errorCode) {
                    $errorStr = xml_error_string($errorCode);
                }
                if ($libxmlError && !$errorStr) {
                    $errorCode = $libxmlError->code;
                    $errorStr = $libxmlError->message;
                }
                // dump("LibXmlError: ".$libxmlError->message."\nError: $errorStr\n");

                return new XMLParserError(
                    errorCode: $errorCode,
                    errorMessage: $errorStr,
                    column: $column,
                    line: $line,
                    byteIndex: $byteIndex
                );
            }
            return null;
        }

        public function free(): void
        {
            libxml_clear_errors();
            libxml_use_internal_errors($this->useLibxmlInternalErrors);
            xml_parser_free($this->parser);
        }

        private function elementStartHandler(XMLParser $parser, string $name, array $attributes): void
        {
            $this->updateEntryType(XMLParserEntryType::ELEMENT_START);
            // dump("ELEMENT START - $name");
            $this->events->elementStartHandler($this, $name, $attributes);
            $this->updatePos();
        }

        private function elementEndHandler(XMLParser $parser, string $name): void
        {
            $this->updateEntryType(XMLParserEntryType::ELEMENT_END);
            $this->events->elementEndHandler($this, $name);
            $this->updatePos();
        }

        private function elementValueHandler(XMLParser $parser, string $data): void
        {
            $this->updateEntryType(XMLParserEntryType::ELEMENT_VALUE);
            $this->events->elementValueHandler($this, $data);
            $this->updatePos();
        }

        private function otherConstructsHandler(XMLParser $parser, string $data): void
        {
            if (Str::startsWith($data, '<!--') && Str::endsWith($data, '-->')) {
                $this->commentHandler($parser, $data);
            } else {
                $this->unsupportedConstructHandler($parser,$data,XMLUnsupportedConstructType::UNKNOWN_CONSTRUCT);
            }
        }

        private function unsupportedConstructHandler(XMLParser $parser, mixed $data,XMLUnsupportedConstructType $type): void{
            $this->updateEntryType(XMLParserEntryType::UNSUPPORTED_ENTITY);
            $this->events->unsupportedConstructHandler($this, $data, $type);
            $this->updatePos();
        }

        private function commentHandler(XMLParser $parser, string $data): void
        {
            $this->updateEntryType(XMLParserEntryType::COMMENT);
            $this->events->commentHandler($this, $data);
            $this->updatePos();
        }

        private function notationDeclHandler(
            XMLParser $parser,
            string $entity_name,
            string|false $base,
            string $system_id,
            string|false $public_id,
            string|false $notation_name
        ): void {
            $this->unsupportedConstructHandler($parser,[
                $entity_name,
                $base,
                $system_id,
                $public_id,
                $notation_name
            ], XMLUnsupportedConstructType::NOTATION_DECLARATION);
        }



        private function startNamespaceHandler(XMLParser $parser, string|false $prefix, string $uri): void
        {
            $this->unsupportedConstructHandler($parser, [
                $prefix,
                $uri
            ], XMLUnsupportedConstructType::START_NAMESPACE_DECLARATION);
        }

        private function unparsedEntityHandler(
            XMLParser $parser,
            string $entity_name,
            string|false $base,
            string $system_id,
            string|false $public_id,
            string|false $notation_name
        ): void {
            $this->unsupportedConstructHandler($parser, [
                $entity_name,
                $base,
                $system_id,
                $public_id,
                $notation_name
            ], XMLUnsupportedConstructType::UNPARSED_ENTITY_DECLARATION);
        }

        private function processingInstructionHandler(XMLParser $parser, string $target, string $data): void
        {
            $this->unsupportedConstructHandler($parser, [
                $target,
                $data
            ], XMLUnsupportedConstructType::PROCESSING_INSTRUCTION);
        }

        private function externalEntityRefHandlerhandler(
            XMLParser $parser,
            string $open_entity_names,
            string|false $base,
            string $system_id,
            string|false $public_id
        ): bool {
            $this->unsupportedConstructHandler($parser, [
                $open_entity_names,
                $base,
                $system_id,
                $public_id
            ], XMLUnsupportedConstructType::EXTERNAL_ENTITY_REFERENCE);
            return false;
        }

        private function isByteIndexValid(?XMLParserEntryType $entryType, int $byteIndex)
        {
            $isByteIndexValid = false;//$this->dataByteIndex >= $byteIndex;
            if ($entryType === XMLParserEntryType::ELEMENT_START) {
                $char = $this->getCharAtByteIndex($byteIndex);
                $isByteIndexValid =
                    $char  === '>';
               // echo "ELEMENT START CHAR: '$char'";
                //dump($char);
            } else if (
                $entryType === XMLParserEntryType::ELEMENT_END
                || $entryType === XMLParserEntryType::COMMENT
            ) {
                $isByteIndexValid = $this->getCharAtByteIndex($byteIndex - 1) === '>';
            }
            // XMLParserEntryType::ELEMENT_VALUE
            else if ($entryType === XMLParserEntryType::ELEMENT_VALUE) {
                if ($isByteIndexValid = $this->positionIsValid()) {
                    $this->getLastValidPosition(
                        column: $validColumn,
                        line: $validLine,
                        byteIndex: $validByteIndex
                    );
                   // echo "\nTHIS WONT WORK ";
                    if($this->entryType === XMLParserEntryType::ELEMENT_START){
                        $nextCharPos = $this->getNextCharPosition();
                        $nextChar = $this->getCharAtByteIndex($nextCharPos);
                      //  echo "NEXT CHAR POS: $nextCharPos ($nextChar)";
                        $isByteIndexValid = $byteIndex >$nextCharPos;
                    }
                    else{
                        $isByteIndexValid = $byteIndex > $validByteIndex;
                    }
                }
            }
            else if($entryType === null){
                $isByteIndexValid = $byteIndex === 0;
            }
             else {
                $isByteIndexValid = true;
            }
            return $isByteIndexValid;
        }

        private function updateEntryType(XMLParserEntryType $entryType)
        {
            // dump("Update entry type {$entryType->name}");
            if ($this->entryType === null && $entryType !== XMLParserEntryType::ELEMENT_START) {
                throw new InternalException(
                    message: "XML file should start with element start, but found '" . $entryType->name . "'.",
                    context: ['entryType' => $entryType, 'this' => $this]
                );
            }
            $this->nextEntryType = $entryType;
        }

        private function printValidPosInfo(){
            $pos = $this->lastValidPos;
            $ch = $this->getCharAtByteIndex($pos[2],emptyIfError:true);
            DebugUtils::log("ValidPosInfo","($ch)" . $pos[0].", " . $pos[1].", " . $pos[2]);
        }

        private function updatePos()
        {
            $this->printValidPosInfo();
            $this->getParserPosition(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );
            $this->getLastValidPosition(
                column: $validColumn,
                line: $validLine,
                byteIndex: $validByteIndex
            );
            $entryTypeName = $this->nextEntryType?->name ?? "NULL";
            $isByteIndexValid = $this->isByteIndexValid($this->nextEntryType, $byteIndex);
            $char = $this->getCharAtByteIndex($byteIndex,emptyIfError:true);
            DebugUtils::log("({$entryTypeName}) :$line, $column, $byteIndex BYTE INDEX IS VALID ($char): '",
            ($isByteIndexValid ? "true" : "false")
        );
            if ($isByteIndexValid) {
                $this->setValidPosition(
                    column: $column,
                    line: $line,
                    byteIndex: $byteIndex
                );
                $this->dataByteIndex += $byteIndex - $validByteIndex;
                $this->shiftData();
            } else {
                $this->setPosition(column: $column, line: $line);
            }
            $this->printValidPosInfo();
            $this->entryType = $this->nextEntryType;
        }

        public function getPos(?int &$column, ?int &$line, ?int &$byteIndex): void
        {
            /*
            <element>   </element>
            */
            if ($this->entryType === XMLParserEntryType::UNSUPPORTED_ENTITY) {
                $this->throwInternalException("Could not get position of unsupported entity");
            }

            if (!$this->positionIsValid()) {
                $this->computeByteIndex();
            }
            $this->getLastValidPosition(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );

            if ($this->entryType === XMLParserEntryType::ELEMENT_START) {
                $byteIndex = $this->getNextCharPosition();
                $column += 1;
            }
            if (
                $this->nextEntryType === XMLParserEntryType::ELEMENT_END
                || $this->nextEntryType === XMLParserEntryType::ELEMENT_START
                || $this->nextEntryType === XMLParserEntryType::COMMENT
            ) {
                $this->shiftData();
                $dataPartByteOffset = $this->dataByteIndex;
                for($i = 0;;++$i){
                    $dataPart = $this->data[$i] ?? $this->dataShouldHaveAtLeastNDataParts($i);
                    $prevByteIndex = $byteIndex;
                    StrUtils::skipWhitesAndAdvancePos(
                        str: $dataPart,
                        byteOffset:$dataPartByteOffset,
                        bytePos: $byteIndex,
                        linePos: $line,
                        columnPos: $column
                    );
                    $dataPartByteOffset += $byteIndex - $prevByteIndex;
                    if($dataPartByteOffset < strlen($dataPart)){
                        break;
                    }
                    array_shift($this->data);
                    $dataPartByteOffset = 0;
                }
            }
        }


        private function computeByteIndex()
        {
            DebugUtils::log("COMPUTING BYTE INDEX for ", $this->entryType->name);

            $this->getLastValidPosition($validCol, $validLine, $validByteIndex);
            $this->getCurrentPosition($column, $line);
            DebugUtils::log("last valid position",":$validLine, $validCol' - byte index: '$validByteIndex'");
            DebugUtils::log("Position",":$line, :$column");

            $byteIndex = $validByteIndex;

            $moveByColumns = $column - 1;
            if ($validLine === $line) {
                $moveByColumns -= $validCol;
            } else {
                $this->shiftData();
                for (; $validLine < $line; ++$validLine) {
                    $dataPart = $this->data[0] ?? $this->dataShouldNotBeEmpty();
                    $pos = 0;
                    while (($pos = strpos($dataPart, "\n", $this->dataByteIndex)) === false) {
                        array_shift($this->data);
                        $byteIndex += strlen($dataPart) - $this->dataByteIndex;
                        $this->dataByteIndex = 0;
                    }
                    $byteIndex += $pos + 1 - $this->dataByteIndex;
                    $this->dataByteIndex = $pos + 1;
                }
            }
            $this->shiftData();
            while ($moveByColumns > 0) {
                $dataPart = $this->data[0] ?? $this->dataShouldNotBeEmpty();
                $byteOffset = StrUtils::utf8TryToGetNthCharByteOffset(
                    str: $dataPart,
                    chOffset: $moveByColumns,
                    byteOffset: $this->dataByteIndex
                );
                $dataPartLen = null;
                if ($byteOffset < 0 || $byteOffset >= ($dataPartLen = strlen($dataPart))) {
                    $dataPartLen = $dataPartLen ?? strlen($dataPart);
                    $moveByColumns -= StrUtils::length(substr($dataPart, $this->dataByteIndex));
                    array_shift($this->data);
                    $byteIndex += $dataPartLen - $this->dataByteIndex;
                    $this->dataByteIndex = 0;
                } else {
                    $byteIndex += $byteOffset - $this->dataByteIndex;
                    $this->dataByteIndex = $byteOffset;
                    $moveByColumns = 0;
                }
            }
            $this->setValidPosition($column, $line, $byteIndex);
            // dump("Computed char '" . $this->getCharAtRelByteIndex(0) . "'");
        }

        public function positionIsValid(): bool
        {
            return $this->lastValidPos[0] === $this->pos[0] && $this->lastValidPos[1] === $this->pos[1];
        }

        public function setValidPosition(int $column, int $line, int $byteIndex): void
        {
            $this->lastValidPos = [$line, $column, $byteIndex];
            $this->setPosition(column: $column, line: $line);
        }

        public function setPosition(int $column, int $line): void
        {
            $this->pos = [$line, $column];
        }

        public function getLastValidPosition(?int &$column, ?int &$line, ?int &$byteIndex): void
        {
            list($line, $column, $byteIndex) = $this->lastValidPos;
        }

        public function getCurrentPosition(?int &$column, ?int &$line): void
        {
            list($line, $column) = $this->pos;
        }

        private function getNextCharPosition(): int
        {
            $this->shiftData();
            while (true) {
                $dataPart = $this->data[0] ?? $this->dataShouldNotBeEmpty();
                $byteOffset = StrUtils::utf8TryToGetNthCharByteOffset($dataPart, 1, $this->dataByteIndex);
                if ($byteOffset >= 0) {
                    return $byteOffset;
                }
                array_shift($this->data);
                $this->dataByteIndex = 0;
            }
        }

        private function getCharAtByteIndex(int $byteIndex,bool $emptyIfError = false): string
        {
            $this->getLastValidPosition($lastPosColumn, $lastPosLine, $lastPosByteIndex);
            $relByteIndex = $byteIndex - $lastPosByteIndex;
            $char = $this->getCharAtRelByteIndex(
                relByteIndex:$relByteIndex,
            emptyIfError:$emptyIfError
        );
            return $char;
        }

        private function getCharAtRelByteIndex(int $relByteIndex,bool $emptyIfError = false): string
        {
            $this->shiftData();
            $currentByteOffset = $this->dataByteIndex;
            for ($i = 0;; ++$i) {
                $dataPart = $this->data[$i] ?? null;
                if($dataPart === null){
                   if($emptyIfError){
                        return "";
                   }
                   else{
                    $this->dataShouldHaveAtLeastNDataParts(
                        minDataLen: $i + 1,
                        context: ['relByteIndex' => $relByteIndex]
                    );
                }
                }
                $dataPartLen = strlen($dataPart);
                $restDataPartLen = $dataPartLen - $currentByteOffset;
                if ($relByteIndex >= $restDataPartLen) {
                    $relByteIndex -= $restDataPartLen;
                } else {
                    $char = StrUtils::utf8GetCharAtIndex($dataPart,index:0,byteOffset: $relByteIndex+$currentByteOffset);
                    return $char;
                }
                $currentByteOffset = 0;
            }
        }

        private function dataShouldHaveAtLeastNDataParts(int $minDataLen, array $context = [])
        {
            $context['minDataLen'] = $minDataLen;
            $this->throwInternalException(
                message: "Data should have at least '$minDataLen' data parts.",
                context: $context
            );
        }

        private function shiftData()
        {
            if ($this->dataByteIndex > 0) {
                while (true) {
                    $dataPart = $this->data[0] ?? null;
                    if ($dataPart === null) {
                        if ($this->dataByteIndex === 0) {
                            return;
                        }
                        $this->dataShouldNotBeEmpty();
                    }
                    $dataPartLen = strlen($dataPart);
                    if ($this->dataByteIndex < $dataPartLen) {
                        return;
                    }
                    $this->dataByteIndex -= $dataPartLen;
                    array_shift($this->data);
                }
            }
        }

        private function throwInternalException(string $message, array $context = [])
        {
            $context['this'] = $this;
            throw new InternalException($message, context: $context);
        }

        private function getParserPosition(?int &$column, ?int &$line, ?int &$byteIndex): void
        {
            $column = self::getValidPos(xml_get_current_column_number($this->parser), $this->parser);
            $line = self::getValidPos(xml_get_current_line_number($this->parser), $this->parser);
            $byteIndex = self::getValidPos(xml_get_current_byte_index($this->parser), $this->parser);
        }

        /**
         * @param int|false $pos
         * @param XMLParser $parser
         * @return int
         * @throws InvalidArgumentException
         */
        private static function getValidPos(int|false $pos, XMLParser $parser): int
        {
            if ($pos === false) {
                throw new InvalidArgumentException(
                    argumentName: "parser",
                    argumentValue: $parser,
                    isNotValidBecause: "method for getting position marked it as invalid, i.e. it failed to get position."
                );
            }
            return $pos;
        }

        private function dataShouldNotBeEmpty(string $message = ""): void
        {
            $this->throwInternalException(
                $message ?: "Data should not be empty"
            );
        }


    }
}
