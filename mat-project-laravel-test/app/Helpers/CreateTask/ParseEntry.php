<?php

namespace App\Helpers\CreateTask\ParseEntry {

    use App\Dtos\Errors\ErrorResponse\BytePositionForGivenLineAndColumn;
    use App\Dtos\Errors\ErrorResponse\ErrorResponse;
    use App\Dtos\Errors\ErrorResponse\HintPosition;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLSyntaxErrorErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLUnsupportedConstructErrorData;
    use App\Dtos\Errors\XML\InvalidElement\InvalidElement;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\InternalException;
    use App\Exceptions\InvalidArgumentException;
    use App\Exceptions\XMLInvalidElementException;
    use App\Exceptions\XMLParsingException;
    use App\Exceptions\XMLSyntaxErrorException;
    use App\Exceptions\XMLUnsupportedConstructException;
    use App\Helpers\CreateTask\Document\Document;
    use App\Helpers\CreateTask\TaskRes;
    use App\Types\BaseXMLParser;
    use App\Types\LibXmlParser;
    use App\Types\TrimType;
    use App\Types\XMLNodeBase;
    use App\Types\XMLContext;
    use App\Types\XMLContextBase;
    use App\Types\XMLParserAccuratePosTracker;
    use App\Types\XMLParserEvents;
    use App\Types\XMLUnsupportedConstructType;
    use App\Utils\DebugUtils;
    use App\Utils\StrUtils;
    use Exception;
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

    class ParseEntry implements XMLParserEvents
    {
        private ?XMLNodeBase $node;
        private ?XMLContext $context;
        private bool $start;

        /**
         * @var string[] $supportedConstructs
         */
        private array $supportedConstructs = ["element", "attribute", "comment", "element value"];



        public function __construct()
        {
            $this->context = null;
            $this->node = Document::create();
            $this->start = true;
        }

        public function elementStartHandler(BaseXMLParser $parser, string $name, array $attributes): void
        {
            if (!$this->start) {
                $this->node = $this->node->getChild($name, $this->context);
            } else {
                $this->start = false;
            }

            $this->node->validateStart(
                name: $name,
                attributes: $attributes,
                context: $this->context
            );
        }

        public function elementEndHandler(BaseXMLParser $parser, string $name): void
        {
            $node = $this->node->validateAndMoveUp($this->context);
            if(!$node){
                dump("Node '".$this->node->getName()."' does not have parent node");
                dump($this->node);
            }
            $this->node = $node;
        }

        public function elementValueHandler(BaseXMLParser $parser, string $data): void
        {
            if($this->node === null){
                if(StrUtils::trimWhites($data,TrimType::TRIM_BOTH)){
                    dump("WTF: $data");
                }
                $parser->getPos($column,$line,$byteIndex);
                dump(":$line, $column, $byteIndex");
            }
            else{
            $this->node->appendValue(value: $data, context: $this->context);
            }
        }

        public function unsupportedConstructHandler(BaseXMLParser $parser, mixed $data, XMLUnsupportedConstructType $type): void
        {
           $name = match($type){
                XMLUnsupportedConstructType::EXTERNAL_ENTITY_REFERENCE => 'external entity reference',
                XMLUnsupportedConstructType::NOTATION_DECLARATION => 'notation declaration',
                XMLUnsupportedConstructType::PROCESSING_INSTRUCTION => 'processing instruction',
                XMLUnsupportedConstructType::START_NAMESPACE_DECLARATION => 'start namespace declaration',
                XMLUnsupportedConstructType::UNPARSED_ENTITY_DECLARATION => 'unparsed entity declaration',
                XMLUnsupportedConstructType::UNKNOWN_CONSTRUCT => 'unknown construct'
            };
            $this->unsupportedConstruct($parser,$name);
        }

       public function commentHandler(BaseXMLParser $parser, string $data): void
       {
        
       }

        /**
         * @param iterable<string> $data
         * @return TaskRes
         */
        public function parse(iterable $data): TaskRes
        {
            $parser = null;
            try {
                $parser = LibXmlParser::create($this);
                $this->context = new XMLContext($parser,new TaskRes());

                $error = null;
                // Parse
                foreach ($data as $dataPart) {
                   if(($error = $parser->parse($dataPart))){
                    break;
                   }
                }

                if($error || ($error = $parser->parse("",isFinal:true))){
                    throw new XMLSyntaxErrorException(
                        description:$error->errorMessage,
                        errorData:XMLSyntaxErrorErrorData::create()
                        ->setLine($error->line)
                        ->setColumn($error->column)
                        ->setByteIndex($error->byteIndex)
                    );
                }
            } catch (ApplicationException $e) {
                // TODO: REMOVE THIS catch
                $errorResponse = $e->getErrorResponse();
                echo "\nAPP ERROR:\n",
                DebugUtils::jsonEncode(ErrorResponse::export($errorResponse)),
                "\n";
                $errorData = $errorResponse->error->details?->errorData;
                if ($errorData) {
                    $byteIndex = is_array($errorData) ? ($errorData['byteIndex'] ?? $errorData['eByteIndex'] ?? false) : ($errorData->{'byteIndex'} ?? $errorData->{'eByteIndex'} ?? false);
                    if (is_int($byteIndex) && $byteIndex >= 0) {
                        $char = substr(implode("", $data), $byteIndex, 1);
                        echo "\nCHAR AT BYTE INDEX '$byteIndex': '$char' (", ord($char), ")\n";
                    }
                    $byteLength =  is_array($errorData) ? ($errorData['byteLength'] ?? false) : ($errorData->{'byteLength'} ?? false);
                    if(is_int($byteLength)){
                        $str = substr(implode("", $data), $byteIndex, $byteLength);
                        echo "\nSTRING AT BYTE INDEX '$byteIndex':\n'$str'";
                    }
                }
                throw new Exception(previous: $e);
            } catch (InternalException $e) {
                echo "\nINTERNAL ERROR:\n",
                DebugUtils::jsonEncode([
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'context' => $e->context()
                ]),
                "\n";
                var_dump($e->context());
                throw new Exception(previous: $e);
            } finally {
                // Free parser
                $parser?->free();
            }
          
            return $this->context->getTaskRes();
        }

        private function unsupportedConstruct(BaseXMLParser $parser,string $constructName): void
        {

            $errorData = XMLUnsupportedConstructErrorData::create();

            $parser
                ->getPos(
                    column: $errorData->column,
                    line: $errorData->line,
                    byteIndex: $errorData->byteIndex
                );

            throw new XMLUnsupportedConstructException(
                constructName: $constructName,
                errorData: $errorData
                    ->setSupportedConstructs($this->supportedConstructs)
            );
        }
    }
}
