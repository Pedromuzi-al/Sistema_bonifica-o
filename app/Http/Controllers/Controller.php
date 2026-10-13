<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function authorizeOrRedirect(string $ability, mixed $arguments = null, ?string $message = null): bool|RedirectResponse
    {
        try {
            $this->authorize($ability, $arguments);

            return true;
        } catch (AuthorizationException $exception) {
            if (request()->expectsJson() || request()->is('api/*')) {
                throw $exception;
            }

            return redirect()->back()->with(
                'permission_error',
                $message ?: $exception->getMessage() ?: 'Você não tem permissão para realizar esta ação.'
            );
        }
    }
}
