<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Exceptions\PermissionDeniedException;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected function checkPermission(string $permission)
    {
        if (!auth()->user()->can($permission)) {
            throw new PermissionDeniedException();
        }
    }
}
