<?php

namespace App\Exceptions;

use Exception;

class ServiceException extends Exception
{
    public function __construct($message = 'Service error occurred', $code = 500)
    {
        parent::__construct($message, $code);
    }
} 