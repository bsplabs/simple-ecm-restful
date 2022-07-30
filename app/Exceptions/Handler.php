<?php

namespace App\Exceptions;

use App\Traits\ApiResponsor;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponsor;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // $this->reportable(function (Throwable $e) {
        //     error_log('Catch exception -----> ');
        //     error_log($e->getMessage());
        // });

        // $this->renderable(function (Throwable $e) {
        //     if ($e instanceof ValidationException) {
        //         $errors = $e->validator->errors()->getMessages();
        //         return $this->errorResponse($errors, 422);
        //     } 
        // });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            $errors = $e->validator->errors()->getMessages();
            return $this->errorResponse($errors, 422);
        }

        if ($e instanceof ModelNotFoundException) {
            $modelName = strtolower(class_basename($e->getModel()));
            return $this->errorResponse('Does not exists any ' . $modelName . ' with the specified identificator', 404);
        }

        if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        }

        if ($e instanceof AuthorizationException) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('The specified method for the request is invalid', 405);
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->errorResponse('The specified URL cannot be found', 404);
        }

        if ($e instanceof HttpException) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }

        if ($e instanceof QueryException) {
            $errorCode = $e->errorInfo[1];

            if ($errorCode == 1451) {
                return $this->errorResponse('Cannot remove this resource permanently. It is related with any other resource', 409);
            }
        }

        // Unexpected Exception
        if (config('app.debug')) {
            return $this->errorResponse('Unexpected exception. Try later', 500);
        }

        return parent::render($request, $e);
    }

    public function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->errorResponse('Unauthenticated.', 401);
    }
}
