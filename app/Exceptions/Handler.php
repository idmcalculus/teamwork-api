<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e);
            }
        });
    }

    /**
     * Handle API exceptions and return standardized JSON responses
     *
     * @param Throwable $exception
     * @return JsonResponse
     */
    private function handleApiException(Throwable $exception): JsonResponse
    {
        if ($exception instanceof AuthenticationException) {
            return $this->errorResponse('Unauthenticated. Please login to continue.', 401);
        }

        if ($exception instanceof AuthorizationException) {
            return $this->errorResponse('You are not authorized to perform this action.', 403);
        }

        if ($exception instanceof ValidationException) {
            return $this->errorResponse(
                'Validation failed.',
                422,
                $exception->errors()
            );
        }

        if ($exception instanceof ModelNotFoundException) {
            $modelName = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse(
                "No {$modelName} found with the specified identifier.",
                404
            );
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->errorResponse('The requested resource was not found.', 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse(
                'The specified method for the request is invalid.',
                405
            );
        }

        if ($exception instanceof HttpException) {
            return $this->errorResponse(
                $exception->getMessage(),
                $exception->getStatusCode()
            );
        }

        if ($exception instanceof QueryException) {
            $errorCode = $exception->errorInfo[1] ?? null;
            
            if ($errorCode == 1451) {
                return $this->errorResponse(
                    'Cannot delete resource because it is related to other resources.',
                    409
                );
            }

            // Log database errors but don't expose details to the client
            report($exception);
            return $this->errorResponse(
                'Database error occurred. Please try again later.',
                500
            );
        }

        // Handle any other exceptions
        if (config('app.debug')) {
            return $this->errorResponse(
                $exception->getMessage(),
                500,
                [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTrace()
                ]
            );
        }

        // Generic error message for production
        return $this->errorResponse(
            'An unexpected error occurred. Please try again later.',
            500
        );
    }

    /**
     * Return a standardized error JSON response
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return JsonResponse
     */
    private function errorResponse(string $message, int $statusCode, ?array $errors = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
