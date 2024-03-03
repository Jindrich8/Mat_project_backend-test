<?php

namespace App\Types {

    use App\Dtos\Defs\Errors\XML\DefsOr;
    use App\Exceptions\InvalidArgumentException;

    enum AndGroupDepth{
        case GROUP_START;
        case GROUP_END;
    }

    class AndOrStringBuilder
    {
        private string $andDel = ',';
        private string $orDel = ' or ';
        /**
         * @var string[] $andGroups
         */
        private array $andGroups = ['(', ')'];
        private int $depth = 0;
        private int $maxDepth = 0;
        /**
         * @var (string|AndGroupDepth)[] $strs
         */
        private array $strs = [];

        /**
         * @param (array{0:string,1:string})[] $andGroups
         */
        public function __construct(string $andDel = ', ',string $orDel = ' or ',array $andGroups = [['(', ')']]){
            $this->setAndDelimiter($andDel);
            $this->setOrDelimiter($orDel);
            $this->setAndGroups($andGroups);
            $this->depth = 0;
            $this->maxDepth = 0;
            $this->strs = [];
        }

        /**
         * @param string[] $andGroups
         */
        public function setAndGroups(array $andGroups){
            if(count($andGroups) % 2 !== 0){
                throw new InvalidArgumentException(
                    argumentName:'andGroups',
                    argumentValue:$andGroups,
                    isNotValidBecause:'it should be array with even number of elements.'
                );
            }
            $this->andGroups = $andGroups;
        }

        public function setAndDelimiter(string $andDel){
            $this->andDel = $andDel;
        }

        public function setOrDelimiter(string $orDel){
            $this->orDel = $orDel;
        }

        private function addAndGroup(AndGroupDepth $gr){
            $this->strs[]=$gr;
        }

        private function increaseDepth(){
            if(++$this->depth > $this->maxDepth){
                $this->maxDepth = $this->depth;
            }
        }

        private function decreaseDepth(){
            --$this->depth;
        }

        /**
         * @param (string|DefsOr)[] $elements
         */
        public function transform(array $elements):string{
            // set depth to -1, because we immediately increase the depth in and function
            $this->depth = -1;
            $this->maxDepth = 0;
            $this->strs = [];

            
            $this->and($elements);
            $maxDepth = $this->maxDepth;
            $andGroupsCount = count($this->andGroups);
            $groupIStart = ($andGroupsCount - $maxDepth*2) & (-2);
            if($groupIStart < 0){
                $groupIStart = 0;
            }

            $str = "";
            $depth = 0;
            while(($strOrGroup = array_shift($this->strs)) !== null){
                if(!is_string($strOrGroup)){
                    $grpI = $groupIStart;
                    if($strOrGroup === AndGroupDepth::GROUP_START){
                        $grpI += $depth*2;
                        ++$depth;
                    }
                    else{
                        --$depth;
                        $grpI += $depth * 2 + 1;
                    }
                    $str.= $this->andGroups[$grpI % $andGroupsCount];
                }
                else{
                    $str.=$strOrGroup;
                }
            }
            $this->strs = [];
            $this->depth = 0;
            $this->maxDepth = 0;
            return $str;
        }

        /**
         * @param (string|DefsOr)[] $a
         */
        private function and(array $a)
        {
            $this->increaseDepth();
            $i = 0;
            if ($a) {
                $inGroup = $this->andGroups && $this->depth >= 1 && count($a) >= 2;
                if ($inGroup) {
                    $this->addAndGroup(AndGroupDepth::GROUP_START);
                }
                while (($item = $a[$i++] ?? null) !== null) {
                    if (is_string($item)) {
                        if ($this->strs) {
                            $this->strs[] = $this->andDel;
                        }
                        $this->strs[] = $item;
                    } else {
                        $this->or($item);
                    }
                }
                if ($inGroup) {
                    $this->addAndGroup(AndGroupDepth::GROUP_END);
                }
            }
            $this->decreaseDepth();
        }

        private function or(DefsOr $o)
        {
            $this->increaseDepth();
            $i = 0;
            if ($o->or) {
                while (($item = $o->or[$i++] ?? null) !== null) {
                    if (is_array($item)) {
                        $this->and($item);
                    } else {
                        if ($this->strs) {
                            $this->strs[] = $this->orDel;
                        }
                        $this->strs[] = $item;
                    }
                }
            }
            $this->decreaseDepth();
        }
    }
}
