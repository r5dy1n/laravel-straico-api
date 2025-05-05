<?php

namespace r5dy1n\Straico;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class StraicoService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(string $apiKey, string $baseUrl, int $timeout = 60)
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Straico API key is required.');
        }
        if (empty($baseUrl)) {
            throw new \InvalidArgumentException('Straico Base URL is required.');
        }

        $this->apiKey = $apiKey;
        // Ensure base URL doesn't have a trailing slash for Guzzle base_uri
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        $this->client = new Client([
            // Base URI is used for relative paths in request methods (like v1 endpoints)
            'base_uri' => $this->baseUrl . '/', // Add trailing slash here for base_uri
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                // Content-Type is set per request type (json or multipart)
            ],
            'timeout' => $this->timeout,
            'http_errors' => false, // Handle errors manually
        ]);
    }

    /**
     * Make a standard request to the Straico API.
     *
     * @param string $method HTTP method (GET, POST, DELETE, etc.)
     * @param string $uri API endpoint URI
     * @param array $options Request options (e.g., json, query)
     * @return array Decoded JSON response
     * @throws \Exception If the request fails or returns an error status code.
     */
    protected function request(string $method, string $uri, array $options = []): array
    {
        // Allow overriding base URI if $uri is a full URL
        $isFullUrl = str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://');
        $requestUri = $isFullUrl ? $uri : ltrim($uri, '/'); // Guzzle needs relative path without leading /

        try {
            $response = $this->client->request($method, $requestUri, $options);
            return $this->decodeResponse($response);
        } catch (RequestException $e) {
            $errorBody = null;
            if ($e->hasResponse()) {
                 try {
                     $errorBody = $this->decodeResponse($e->getResponse());
                 } catch (\Exception $decodeException) {
                     $errorBody = (string) $e->getResponse()->getBody();
                 }
            }
            throw new \Exception(
                'Straico API request failed: ' . $e->getMessage() . ' - ' . json_encode($errorBody),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            throw new \Exception('Straico API request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

     /**
     * Make a multipart/form-data request to the Straico API.
     * Used for endpoints involving file uploads.
     *
     * @param string $method HTTP method (Should be POST)
     * @param string $uri API endpoint URI
     * @param array $multipartData Array conforming to Guzzle's multipart format.
     * @return array Decoded JSON response
     * @throws \Exception If the request fails or returns an error status code.
     */
    protected function requestMultipart(string $method, string $uri, array $multipartData): array
    {
        // Allow overriding base URI if $uri is a full URL
        $isFullUrl = str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://');
        $requestUri = $isFullUrl ? $uri : ltrim($uri, '/'); // Guzzle needs relative path without leading /

        try {
            // Ensure Authorization header is included for multipart requests too
            $options = ['multipart' => $multipartData];
            $response = $this->client->request($method, $requestUri, $options);
            return $this->decodeResponse($response);
        } catch (RequestException $e) {
            $errorBody = null;
            if ($e->hasResponse()) {
                 try {
                     $errorBody = $this->decodeResponse($e->getResponse());
                 } catch (\Exception $decodeException) {
                     $errorBody = (string) $e->getResponse()->getBody();
                 }
            }
            throw new \Exception(
                'Straico API multipart request failed: ' . $e->getMessage() . ' - ' . json_encode($errorBody),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            throw new \Exception('Straico API multipart request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Decode the JSON response from the API.
     *
     * @param ResponseInterface $response
     * @return array
     * @throws \Exception If JSON decoding fails.
     */
    protected function decodeResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Include the raw body in the error for better debugging
            $maxLength = 500; // Limit length to avoid huge error messages
            $truncatedBody = strlen($body) > $maxLength ? substr($body, 0, $maxLength) . '...' : $body;
            throw new \Exception(
                'Failed to decode Straico API JSON response: ' . json_last_error_msg() .
                ' | Raw Response Body (truncated): ' . $truncatedBody
            );
        }

        // Check for success indicator and data key
        if (isset($decoded['success']) && $decoded['success'] === true) {
             // If 'data' key exists, return it, otherwise return the whole decoded body (e.g., for simple success messages)
             return $decoded['data'] ?? $decoded;
        } elseif (isset($decoded['success']) && $decoded['success'] === false) {
            // Handle API-level errors indicated by 'success: false'
            $errorMessage = $decoded['message'] ?? ($decoded['error'] ?? json_encode($decoded)); // Look for 'message' or 'error'
             throw new \Exception('Straico API Error: ' . $errorMessage);
        }

        // If the structure doesn't match expected success/error format, assume it might be
        // an older endpoint or different structure, return decoded body but log a warning?
        // For now, return as is. Consider adding logging for unexpected structures.
        // Log::warning('Unexpected Straico API response structure: ' . $body);
         return $decoded;
    }

    // --- API Methods ---

    /**
     * Lists the currently available models (chat and image).
     * Corresponds to: GET /models
     *
     * @return array An array containing 'chat' and 'image' model lists.
     * @throws \Exception
     */
    public function listModels(): array
    {
        return $this->request('GET', '/v1/models');
    }

    /**
     * Creates completions based on a prompt, potentially using multiple models and context from files, YouTube URLs, and images.
     * Corresponds to: POST /prompt/completion
     *
     * @param array $params Parameters including:
     *                      - models (array): Required. List of model identifiers.
     *                      - message (string): Required. The prompt message.
     *                      - file_urls (array): Optional. List of URLs for file context.
     *                      - youtube_urls (array): Optional. List of YouTube video URLs for context.
     *                      - images (array): Optional. List of image URLs for context.
     * @return array The completion results, including overall price/words and individual model completions.
     * @throws \Exception|\InvalidArgumentException
     */
    public function createPromptCompletion(array $params): array
    {
        if (!isset($params['models']) || !is_array($params['models']) || empty($params['models'])) {
            throw new \InvalidArgumentException('The "models" parameter (non-empty array) is required for createPromptCompletion.');
        }
        if (!isset($params['message']) || !is_string($params['message'])) {
            throw new \InvalidArgumentException('The "message" parameter (string) is required for createPromptCompletion.');
        }

        // Optional parameter validation (ensure they are arrays if provided)
        $optionalArrays = ['file_urls', 'youtube_urls', 'images'];
        foreach ($optionalArrays as $key) {
            if (isset($params[$key]) && !is_array($params[$key])) {
                 throw new \InvalidArgumentException("The \"{$key}\" parameter must be an array if provided.");
            }
        }

        return $this->request('POST', '/v1/prompt/completion', ['json' => $params]);
    }

    /**
     * Uploads a file to be used as context.
     * Corresponds to: POST /v0/file/upload
     * Note: This uses the v0 endpoint path.
     *
     * @param string $filePath The path to the local file to upload.
     * @param string $fileName Optional. The filename to use for the upload. Defaults to basename($filePath).
     * @return array The response containing the URL of the uploaded file, e.g., ['url' => '...'].
     * @throws \Exception|\InvalidArgumentException
     */
    public function uploadFile(string $filePath, ?string $fileName = null): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found at path: {$filePath}");
        }
        if (!is_readable($filePath)) {
             throw new \InvalidArgumentException("File is not readable at path: {$filePath}");
        }

        $multipart = [
            [
                'name'     => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => $fileName ?? basename($filePath)
            ]
        ];

        // Construct the full path relative to the host, ignoring the configured base_uri path prefix (/v1)
        $baseUri = $this->client->getConfig('base_uri');
        $host = $baseUri ? rtrim($baseUri->getScheme() . '://' . $baseUri->getAuthority(), '/') : null;

        if (!$host) {
             throw new \Exception('Could not determine base host from Guzzle client configuration.');
        }

        $uploadUri = $host . '/v0/file/upload'; // Use absolute URL for v0 endpoint

        return $this->requestMultipart('POST', $uploadUri, $multipart);
    }

    /**
     * Generates images based on a description using a specified model.
     * Corresponds to: POST /v0/image/generation
     * Note: This uses the v0 endpoint path.
     *
     * @param array $params Parameters including:
     *                      - model (string): Required. Image model identifier (e.g., "openai/dall-e-3").
     *                      - description (string): Required. Text description for image generation.
     *                      - size (string): Optional. Image size ("square", "landscape", "portrait"). Defaults may apply based on model.
     *                      - variations (int): Optional. Number of image variations to generate. Defaults to 1.
     * @return array The response containing URLs for the generated images/zip and pricing info.
     * @throws \Exception|\InvalidArgumentException
     */
    public function createImageGeneration(array $params): array
    {
        if (!isset($params['model']) || !is_string($params['model'])) {
            throw new \InvalidArgumentException('The "model" parameter (string) is required for createImageGeneration.');
        }
        if (!isset($params['description']) || !is_string($params['description'])) {
            throw new \InvalidArgumentException('The "description" parameter (string) is required for createImageGeneration.');
        }

        // Construct the full path relative to the host for the v0 endpoint
        $baseUri = $this->client->getConfig('base_uri');
        $host = $baseUri ? rtrim($baseUri->getScheme() . '://' . $baseUri->getAuthority(), '/') : null;

        if (!$host) {
             throw new \Exception('Could not determine base host from Guzzle client configuration.');
        }

        $generationUri = $host . '/v0/image/generation'; // Use absolute URL for v0 endpoint

        // This endpoint uses standard JSON request, not multipart
        return $this->request('POST', $generationUri, ['json' => $params]);
    }

}