<?php

namespace App\Exceptions;

use Exception;

class PermissionDeniedException extends Exception
{
    public function __construct($message = 'You do not have permission to perform this action.', $code = 403)
    {
        parent::__construct($message, $code);
    }
} 