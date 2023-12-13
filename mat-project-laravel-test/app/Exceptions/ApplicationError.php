<?php

namespace App\Exceptions;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class ApplicationError implements Arrayable, Jsonable, JsonSerializable
{
    public function __construct(private readonly string $help = '', private readonly string $error = '')
    {
    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'help' => $this->help,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @throws \Throwable
     */
    public function toJson($options = 0.0): string
    {
        $jsonEncoded = json_encode($this->jsonSerialize(), $options);
        if(!$jsonEncoded) throw new JsonEncodeException();
        return $jsonEncoded;
    }
}
