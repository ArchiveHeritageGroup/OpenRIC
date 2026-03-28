<?php

declare(strict_types=1);

namespace OpenRiC\Triplestore\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when triplestore operations fail.
 *
 * Covers HTTP communication errors, SPARQL syntax errors,
 * and unexpected responses from the Fuseki endpoint.
 */
class TriplestoreException extends RuntimeException
{
    private int $httpStatusCode;

    private string $sparqlQuery;

    private string $responseBody;

    public function __construct(
        string $message = '',
        int $httpStatusCode = 0,
        string $sparqlQuery = '',
        string $responseBody = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);

        $this->httpStatusCode = $httpStatusCode;
        $this->sparqlQuery = $sparqlQuery;
        $this->responseBody = $responseBody;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getSparqlQuery(): string
    {
        return $this->sparqlQuery;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * Create an exception for an HTTP communication failure.
     */
    public static function httpError(
        int $statusCode,
        string $responseBody,
        string $sparqlQuery = '',
    ): self {
        return new self(
            message: "Fuseki returned HTTP {$statusCode}",
            httpStatusCode: $statusCode,
            sparqlQuery: $sparqlQuery,
            responseBody: substr($responseBody, 0, 2000),
        );
    }

    /**
     * Create an exception for a connection failure.
     */
    public static function connectionFailed(string $endpoint, ?Throwable $previous = null): self
    {
        return new self(
            message: "Failed to connect to Fuseki at {$endpoint}",
            previous: $previous,
        );
    }

    /**
     * Create an exception for an invalid SPARQL response.
     */
    public static function invalidResponse(string $detail, string $responseBody = ''): self
    {
        return new self(
            message: "Invalid response from Fuseki: {$detail}",
            responseBody: substr($responseBody, 0, 2000),
        );
    }
}
