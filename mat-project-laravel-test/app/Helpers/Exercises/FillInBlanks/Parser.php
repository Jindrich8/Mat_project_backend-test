<?php

namespace App\Helpers\Exercises\FillInBlanks {

    use App\Dtos\InternalTypes\FillInBlanksContent\Combobox;
    use App\Dtos\InternalTypes\FillInBlanksContent\FillInBlanksContent;
    use App\Dtos\InternalTypes\FillInBlanksContent\TextInput;
    use App\Exceptions\InternalException;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\GetXMLParserPosition;
    use App\Types\TrimType;
    use App\Types\XMLContextBase;
    use App\Types\XMLDynamicNodeBase;
    use App\Types\XMLValidParserPosition;
    use App\Utils\StrUtils;
    use App\Utils\Utils;

    enum State
    {
        case CMP;
        case TEXT;
    }

    abstract class Parser extends XMLDynamicNodeBase
    {
        private State $state;
        /**
         * @var array<string,int> $tokens
         */
        private array $tokens;

        protected ?FillInBlanksContent $content;
        private bool $hasUiCmp;
        private bool $trimming;

        /**
         * @var string $prevToken
         * Empty string means no token
         */
        private string $prevToken;
        /**
         * @var string $prevNotEscToken
         * Empty string means no token
         */
        private string $prevNotEscToken;
        private string $prevText;
        private XMLValidParserPosition $prevTokenPos;
        private XMLValidParserPosition $prevNotEscTokenPos;
        private bool $hasPrevCmpStartToken;
        private bool $escapeNextChar;
        private XMLValidParserPosition $prevCmpStartTokenPos;

        private string $cmpStartToken;
        private string $cmpEndToken;
        private string $cmbValuesSepToken;
        private string $escapeToken;

        /**
         * @var array<string,string> $cmbValues
         */
        private array $cmbValues;
        private ?string $cmbSelectedValue;

        public function reset()
        {
            parent::reset();
            $config = TaskSrcConfig::get()->getFillInBlanksConfig();
            $this->cmpStartToken = $config->uiCmpStart;
            $this->cmpEndToken = $config->uiCmpEnd;
            $this->cmbValuesSepToken = $config->cmbValuesSep;
            $this->escapeToken = $config->escape;

            $this->tokens = [
                $this->escapeToken => $this->escapeToken,
                $this->cmpStartToken => $this->cmpStartToken,
                $this->cmpEndToken => $this->cmpEndToken
            ];
            $this->state = State::TEXT;
            $this->prevToken = "";
            $this->prevNotEscToken = "";
            $this->prevText = "";
            $this->prevTokenPos = new XMLValidParserPosition();
            $this->prevNotEscTokenPos = new XMLValidParserPosition();
            $this->prevCmpStartTokenPos = new XMLValidParserPosition();
            $this->hasPrevCmpStartToken = false;
            $this->cmbValues = [];
            $this->cmbSelectedValue = null;
            $this->content = null;
            $this->hasUiCmp = false;
            $this->trimming = true;
            $this->escapeNextChar = false;
        }

        protected function getContent(){
            if(!$this->content){
                $this->throwInternalException(
                    message:"Content should not be null!"
                );
            }
            return $this->content;
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            parent::validateStart($attributes,$context,$name);
            $this->state = State::TEXT;
            $this->hasUiCmp = false;
            $this->trimming = true;
            $this->escapeNextChar = false;
            $this->hasPrevCmpStartToken = false;
            $this->prevTokenPos->setPos(1,1,0);
            $this->prevNotEscTokenPos->setPos(1,1,0);
        }

        public function appendValue(string $value, XMLContextBase $context): void
        {
            if($this->trimming){
               $value = StrUtils::trimWhites($value,TrimType::TRIM_START);
               if(!$value){
                return;
               }
               $this->trimming = false;
            }
            $this->parse($value,$context);
        }


        protected function validate(XMLContextBase $context): void
        {
            parent::validate($context);
            $this->prevText = StrUtils::trimWhites($this->prevText,TrimType::TRIM_END);
            if ($this->prevText) {
                if($this->state !== State::TEXT){
                    $context->getPos(
                        column:$column,
                        line:$line,
                        byteIndex:$byteIndex
                    );
                    $this->prevCmpStartTokenPos->getPos(
                        column:$prevColumn,
                        line:$prevLine,
                        byteIndex:$prevByteIndex
                    );
                    $byteLength = $byteIndex - $prevByteIndex;

                    $this->missingCmpEndToken(
                    column:$prevColumn,
                    line:$prevLine,
                    byteIndex:$prevByteIndex,
                    byteLength:$byteLength
                );
                }
                $this->addItemToContent($this->prevText);
            }
            if (!$this->content->structure) {
                $this->valueShouldNotBeEmpty(
                    description: "Fill in blanks exercise content should not be empty"
                );
            }
            if (!$this->hasUiCmp) {
                $this->invalidValue(
                    description: "Fill in blanks exercise content must contain at least one fillable component (combobox or text input)"
                );
            }
            $this->reset();
        }


        private function changeState(State $newState)
        {
            if ($newState === $this->state) {
                return;
            }
            if ($newState === State::CMP) {
                $this->tokens[$this->cmbValuesSepToken] = $this->cmbValuesSepToken;
            } else {
                unset($this->tokens[$this->cmbValuesSepToken]);
            }
            $this->cmbSelectedValue = null;
            $this->cmbValues = [];
            $this->state = $newState;
        }

        private function getAndResetPrevText()
        {
            $prevText = $this->prevText;
            $this->prevText = "";
            return $prevText;
        }

        private function addItemToContent(string|Combobox|TextInput $item)
        {
            if (!is_string($item)) {
                $this->hasUiCmp = true;
            } else if (!$item) {
                // empty string
                return;
            }
            $this->content->structure ??= [];
            $this->content->structure[] = $item;
        }

        private function currentCmpIsCombobox(): bool
        {
            return $this->cmbValues ? true : false;
        }

        private function addComboboxOptionWithoutPrevText(string $text, int $endByteIndex)
        {
            $prevText = $this->getAndResetPrevText();
            $option = $prevText . $text;
            if (!$this->cmbValues) {
                $this->cmbSelectedValue = $option;
            } else if (Utils::arrayHasKey($this->cmbValues, $option)) {
                $this->prevNotEscTokenPos->getPos(
                    column: $column,
                    line: $line,
                    byteIndex: $byteIndex
                );
                ++$column;
                $byteIndex += strlen($this->prevToken);
                /* Prev token can be from previous append value call and
                 there can be extra characters between these two calls,
                    so we need to subtract byte position of prev text start from
                    end byte text position to get real byte length
                    */
                $byteLength = $endByteIndex - $byteIndex;
                $this->duplicateComboboxOption(
                    $option,
                    column: $column,
                    line: $line,
                    byteIndex: $byteIndex,
                    byteLength: $byteLength
                );
            }
            $this->cmbValues[$option] = $option;
        }

        private function addCurrentComboboxToContent()
        {
            shuffle($this->cmbValues);
            /**
             * @var int|false $index
             * This needs to be integer, because shuffle transforms underlying array to list
             */
            $index =  array_search($this->cmbSelectedValue, $this->cmbValues);
            if ($index === false) {
                // internal error - cmbSelectedValue not found
                $this->throwInternalException(
                    "SelectedCmbValue was not found!",
                    context: [
                        'cmbValues' => $this->cmbValues,
                        'selectedCmbValue' => $this->cmbSelectedValue,
                    ]
                );
            }
                $this->addItemToContent(Combobox::create()
                ->setSelectedIndex($index)
                ->setValues($this->cmbValues)
        );
            $this->cmbValues = [];
            $this->cmbSelectedValue = null;
        }

        private function addTextInputWithoutPrevTextToContent(string $text, int $endByteIndex)
        {
            $prevText = $this->getAndResetPrevText();
            $correctText = $prevText . $text;
            $this->addItemToContent(
                TextInput::create()
                    ->setCorrectText($correctText)
            );
        }



        protected function parse(string $input, XMLContextBase $context)
        {
            $byteOffset = 0;

            $textByteOffset = $byteOffset;
            $context->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );

            if ($this->escapeNextChar && $this->prevToken === $this->escapeToken) {
                $this->escapeNextChar = false;
                $ch = StrUtils::utf8GetCharAtIndex($input, index: 0);
                if (!Utils::arrayHasKey($this->tokens, $ch)) {
                    $this->prevTokenPos->getPos(
                        column:$prevColumn,
                        line:$prevLine,
                        byteIndex:$prevByteIndex
                    );

                    $this->invalidEscapeSequence(
                        ch: $ch,
                        column:$prevColumn,
                        line:$prevLine,
                        byteIndex:$byteIndex,
                        byteLength:$byteIndex-$prevByteIndex+strlen($ch)
                    );
                }
                $byteOffset = strlen($ch);
                ++$column;
            }

            dump("VALUE: '$input'");
            $token = "";
            for (;; $textByteOffset = (int)($byteOffset + strlen($token))) {
                if ($token) {
                    $this->prevTokenPos->setPos(
                        column: $column,
                        line: $line,
                        byteIndex: $byteIndex + $byteOffset
                    );
                    $this->prevToken = $token;
                    if($token !== $this->escapeToken){
                        $this->prevNotEscTokenPos
                        ->setPosFromProvider($this->prevTokenPos);
                        if($token === $this->cmpStartToken){
                            $this->prevCmpStartTokenPos
                            ->setPosFromProvider($this->prevTokenPos);
                            $this->hasPrevCmpStartToken = true;
                        }
                    }
                    $byteOffset+=strlen($token);
                    ++$column;
                }
                dump("Byte offset: '$byteOffset'");
                dump($this->tokens);
                $token = StrUtils::utf8GetFirstSetKeyAndAdvancePos(
                    str: $input,
                    set: $this->tokens,
                    columnPos: $column,
                    linePos: $line,
                    byteOffset: $byteOffset
                );
                
                dump(":$line, $column - token: $token");

                if (!$token) {
                    $this->prevText .= substr($input, $textByteOffset);
                    return;
                }
                if ($token === $this->escapeToken) {
                    $nextChar = StrUtils::utf8GetCharAtIndex($input, index: 1, byteOffset: $byteOffset);
                    if (!$nextChar) {
                        $this->escapeNextChar = true;
                        $this->prevText .= substr($input,$textByteOffset);
                        return;
                    }
                    if (!Utils::arrayHasKey($this->tokens, $nextChar)) {
                        $this->invalidEscapeSequence(
                            ch: $nextChar,
                            column: $column,
                            line: $line,
                            byteIndex: $byteIndex + $byteOffset,
                            byteLength:strlen($token)+strlen($nextChar)
                        );
                    }
                    $this->prevText .= StrUtils::substrAsciiBetween($input,$textByteOffset,$byteOffset);
                    $byteOffset += strlen($nextChar);
                    ++$column;
                } else if ($token === $this->cmpStartToken) {
                    if ($this->state === State::CMP) {
                        // invalid open sequence
                        $this->invalidCmpStartToken(
                            column: $column,
                            line: $line,
                            byteIndex: $byteIndex
                        );
                    }
                    $text =$this->getAndResetPrevText()
                     .StrUtils::substrAsciiBetween($input, $textByteOffset, $byteOffset)
                        ;
                    $this->addItemToContent($text);
                    $this->changeState(State::CMP);
                } else if ($token === $this->cmbValuesSepToken) {
                    if ($this->state !== State::CMP) {
                        $this->throwInternalException(
                            message: "State mismatch!\nState should be '" . State::CMP->name . "'.",
                            context: [
                                'input' => $input,
                                'context' => $context,
                                'column' => $column,
                                'line' => $line,
                                'byteIndex' => $byteIndex,
                                'byteOffset' => $byteOffset
                            ]
                        );
                    }

                    $text = StrUtils::substrAsciiBetween($input, $textByteOffset, $byteOffset);
                    $this->addComboboxOptionWithoutPrevText(
                        text: $text,
                        endByteIndex: $byteIndex + $byteOffset
                    );
                } else if ($token === $this->cmpEndToken) {
                    if ($this->state !== State::CMP) {
                        $this->invalidCmpEndToken(
                            column: $column,
                            line: $line,
                            byteIndex: $byteIndex + $byteOffset
                        );
                    }
                    $text = StrUtils::substrAsciiBetween($input, $textByteOffset, $byteOffset);
                    if ($this->currentCmpIsCombobox()) {
                        $this->addComboboxOptionWithoutPrevText(
                            text: $text,
                            endByteIndex: $byteIndex + $byteOffset
                        );
                        $this->addCurrentComboboxToContent();
                    } else {
                        $this->addTextInputWithoutPrevTextToContent(
                            text: $text,
                            endByteIndex: $byteIndex + $byteOffset
                        );
                    }
                    $this->changeState(State::TEXT);
                } else {
                    $this->throwInternalException(
                        message: "Unexpected token '$token'",
                        context: [
                            'input' => $input,
                            'context' => $context,
                            'column' => $column,
                            'line' => $line,
                            'byteIndex' => $byteIndex,
                            'byteOffset' => $byteOffset
                        ]
                    );
                }
            }
        }



        protected function throwInternalException(string $message, array $context = [])
        {
            throw new InternalException(
                message: $message,
                context: ['this' => $this, $context]
            );
        }

        private function missingCmpEndToken(
            int $column,
            int $line,
            int $byteIndex,
            int $byteLength
        ){
            $this->invalidValuePart(
                column:$column,
                line:$line,
                byteIndex:$byteIndex,
                byteLength:$byteLength,
                message:"Unclosed fillable component",
                description:"Close it with '{$this->cmpEndToken}' character."
            );
        }

        private function invalidCmpStartToken(
            int $column,
            int $line,
            int $byteIndex
        ) {
            $this->invalidValuePart(
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                byteLength: strlen($this->cmpStartToken),
                message: "Invalid open fillable component character '{$this->cmpStartToken}'",
                description: "Escape it with '{$this->escapeToken}' character."
            );
        }

        private function invalidCmpEndToken(
            int $column,
            int $line,
            int $byteIndex
        ) {
            $this->invalidValuePart(
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                byteLength: strlen($this->cmpStartToken),
                message: "Invalid close fillable component character '{$this->cmpEndToken}'",
                description: "Escape it with '{$this->escapeToken}' character."
            );
        }

        private function invalidEscapeSequence(
            string $ch,
            int $column,
            int $line,
            int $byteIndex,
            int $byteLength
        ) {
            $this->invalidValuePart(
                byteLength: $byteLength,
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                message: "Unknown escape sequence '{$this->prevToken}{$ch}'",
                description: "There is no need to escape '{$ch}'."
            );
        }

        private function duplicateComboboxOption(
            string $option,
            int $column,
            int $line,
            int $byteIndex,
            int $byteLength = -1
        ) {
            if ($byteLength < 0) {
                $byteLength = strlen($option);
            }
            // duplicate combobox option
            $this->invalidValuePart(
                byteLength: $byteLength,
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                message: "Combobox already has '{$option}' option",
                description: "Combobox needs to have unique options"
            );
        }
    }
}
