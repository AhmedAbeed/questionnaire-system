<?php

namespace App\Exceptions;

use Exception;

class BusinessValidationException extends Exception
{
    public function __construct($message = 'Business validation failed', $code = 422)
    {
        parent::__construct($message, $code);
    }
} 