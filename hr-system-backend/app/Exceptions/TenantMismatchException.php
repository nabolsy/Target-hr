<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantMismatchException extends HttpException
{
    public function __construct(string $message = 'Tenant mismatch detected.')
    {
        parent::__construct(403, $message);
    }
}
