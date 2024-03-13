<?php

namespace App\Types {

    use Illuminate\Support\Facades\DB;
    use \Illuminate\Database\Query\Builder;

    class SimpleQueryWheresBuilder
    {

        private Builder $builder;

        public static function construct(?Builder $builder = null): static
        {
            return new static($builder);
        }

        public function __construct(?Builder $builder = null)
        {
            $this->builder = $builder ?? DB::table("SOME TABLE")->select();
        }

        /**
         * Add a basic where clause to the query.
         *
         * @param  \Closure|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
         * @param  mixed  $operator
         * @param  mixed  $value
         * @param  string  $boolean
         * @return $this
         */
        public function where($column, $operator = null, $value = null, $boolean = 'and')
        {
            $this->builder->where($column, $operator, $value, $boolean);
        }

        public function getWheresStr()
        {
            return DB::getQueryGrammar()->compileWheres($this->builder);
        }
    }
}
