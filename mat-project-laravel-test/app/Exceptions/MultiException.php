<?php

namespace App\Exceptions;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * @template T extends \Exception
 */
class MultiException extends \Exception
{
    /**
     * @param array<T> $exceptions
     */
    public function __construct(private readonly array $exceptions)
    {
    }

    /**
     * @return array<T>
     */
    public function getExceptions():array{
        return $this->exceptions;
    }
}
