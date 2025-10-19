<?php

namespace Tests\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Trait cho API testing với HTTP client
 */
trait ApiTestTrait
{
    protected ?Client $httpClient = null;
    protected string $baseUrl = 'http://localhost:8000';

    /**
     * Setup HTTP client cho testing
     */
    protected function setUpApiClient(): void
    {
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'verify' => false,
            'http_errors' => false, // Không throw exception cho HTTP errors
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'PHPUnit-Test-Client'
            ]
        ]);
    }

    /**
     * Get HTTP client
     */
    protected function getHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->setUpApiClient();
        }
        
        return $this->httpClient;
    }

    /**
     * Make GET request
     */
    protected function get(string $url, array $headers = []): Response
    {
        try {
            return $this->getHttpClient()->get($url, [
                'headers' => array_merge($this->getDefaultHeaders(), $headers)
            ]);
        } catch (RequestException $e) {
            throw new \Exception("GET request failed: " . $e->getMessage());
        }
    }

    /**
     * Make POST request
     */
    protected function post(string $url, array $data = [], array $headers = []): Response
    {
        try {
            return $this->getHttpClient()->post($url, [
                'json' => $data,
                'headers' => array_merge($this->getDefaultHeaders(), $headers)
            ]);
        } catch (RequestException $e) {
            throw new \Exception("POST request failed: " . $e->getMessage());
        }
    }

    /**
     * Make PUT request
     */
    protected function put(string $url, array $data = [], array $headers = []): Response
    {
        try {
            return $this->getHttpClient()->put($url, [
                'json' => $data,
                'headers' => array_merge($this->getDefaultHeaders(), $headers)
            ]);
        } catch (RequestException $e) {
            throw new \Exception("PUT request failed: " . $e->getMessage());
        }
    }

    /**
     * Make DELETE request
     */
    protected function delete(string $url, array $headers = []): Response
    {
        try {
            return $this->getHttpClient()->delete($url, [
                'headers' => array_merge($this->getDefaultHeaders(), $headers)
            ]);
        } catch (RequestException $e) {
            throw new \Exception("DELETE request failed: " . $e->getMessage());
        }
    }

    /**
     * Make request với authentication
     */
    protected function authenticatedRequest(string $method, string $url, array $data = [], array $headers = []): Response
    {
        $headers['Authorization'] = 'Bearer ' . $this->getAuthToken();
        
        switch (strtoupper($method)) {
            case 'GET':
                return $this->get($url, $headers);
            case 'POST':
                return $this->post($url, $data, $headers);
            case 'PUT':
                return $this->put($url, $data, $headers);
            case 'DELETE':
                return $this->delete($url, $headers);
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
        }
    }

    /**
     * Get default headers
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'PHPUnit-Test-Client'
        ];
    }

    /**
     * Get authentication token (mock implementation)
     */
    protected function getAuthToken(): string
    {
        // Trong thực tế, đây sẽ là token từ login response
        return 'test-token-' . time();
    }

    /**
     * Assert response status code
     */
    protected function assertResponseStatus(Response $response, int $expectedStatus): void
    {
        $this->assertEquals(
            $expectedStatus,
            $response->getStatusCode(),
            "Expected status {$expectedStatus}, got {$response->getStatusCode()}. Response body: " . $response->getBody()
        );
    }

    /**
     * Assert response is JSON
     */
    protected function assertJsonResponse(Response $response): void
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('application/json', $contentType);
        
        $body = $response->getBody()->getContents();
        $this->assertJson($body);
    }

    /**
     * Assert response contains specific data
     */
    protected function assertResponseContains(Response $response, array $expectedData): void
    {
        $this->assertJsonResponse($response);
        
        $body = $response->getBody()->getContents();
        $actualData = json_decode($body, true);
        
        foreach ($expectedData as $key => $value) {
            $this->assertArrayHasKey($key, $actualData);
            $this->assertEquals($value, $actualData[$key]);
        }
    }

    /**
     * Get response data as array
     */
    protected function getResponseData(Response $response): array
    {
        $body = $response->getBody()->getContents();
        return json_decode($body, true) ?? [];
    }

    /**
     * Assert response has pagination
     */
    protected function assertResponseHasPagination(Response $response): void
    {
        $data = $this->getResponseData($response);
        
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertArrayHasKey('current_page', $data['pagination']);
        $this->assertArrayHasKey('total', $data['pagination']);
        $this->assertArrayHasKey('per_page', $data['pagination']);
    }

    /**
     * Login và get token (mock implementation)
     */
    protected function loginAsUser(string $username = 'student1', string $password = 'password123'): string
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password
        ]);
        
        $this->assertResponseStatus($response, 200);
        $data = $this->getResponseData($response);
        
        $this->assertArrayHasKey('token', $data);
        return $data['token'];
    }

    /**
     * Upload file test
     */
    protected function uploadFile(string $url, string $filePath, string $fieldName = 'file', array $additionalData = []): Response
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }
        
        $client = $this->getHttpClient();
        
        $multipart = [
            [
                'name' => $fieldName,
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath)
            ]
        ];
        
        foreach ($additionalData as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => $value
            ];
        }
        
        return $client->post($url, [
            'multipart' => $multipart,
            'headers' => $this->getDefaultHeaders()
        ]);
    }
}
