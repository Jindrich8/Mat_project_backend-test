<?php

namespace App\Helpers\CreateTask {

    use App\Helpers\CreateTask\TaskRes;
    use App\Helpers\CreateTask\XMLNoValueNode;
    use App\Helpers\CreateTask\XMLOneUseNode;
    use App\TableSpecificData\TaskDisplay;
    use App\Utils\Utils;
    use Illuminate\Support\Str;

    class GroupNode extends XMLNodeBase{
        private int $depth;
        /**
         * @var int[] $indexes
         */
        private array $indexes;
        public function __construct()
        {
            parent::__construct('group',children:[
                new XMLOneUseNode(
                    'resources',
                    validateStart:function(XMLOneUseNode $thisNode,iterable $attributes,TaskRes $taskRes,?string $name)use($this){
                        
                        if(Utils::lastArrayValue($this->indexes) !== null)
                        $taskRes->groups[Utils::lastArrayValue($this->indexes)];
                    }
                )
                ]);
        }

        function getRequiredAttributes(): array
        {
            // TODO:
            throw null;
        }

        function getNonRequiredAttributes(): array
        {
            // TODO
            throw null;
        }
       function appendValue(string $value, TaskRes $taskRes, callable $getParserPosition): void
       {
        throw null;
       }

        function validate(TaskRes $taskRes): void
        {
            throw null;
        }

}
}