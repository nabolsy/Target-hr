<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    public function __construct(string $message, int $code = 422)
    {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => class_basename($this),
        ], $this->getCode());
    }
}
