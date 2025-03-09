<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    /**
     * @var array|null Additional error details
     */
    protected ?array $errors;

    /**
     * @var int HTTP status code
     */
    protected int $statusCode;

    /**
     * Create a new API exception instance.
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'An error occurred',
        int $statusCode = 400,
        ?array $errors = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    /**
     * Get the errors array.
     *
     * @return array|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Render the exception as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $this->getMessage(),
        ];

        if ($this->errors) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }
}
