<?php

namespace XRPLSale;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use XRPLSale\Exceptions\XRPLSaleException;
use XRPLSale\Exceptions\AuthenticationException;
use XRPLSale\Exceptions\ValidationException;
use XRPLSale\Exceptions\NotFoundException;
use XRPLSale\Exceptions\RateLimitException;
use XRPLSale\Services\AuthService;
use XRPLSale\Services\ProjectsService;
use XRPLSale\Services\InvestmentsService;
use XRPLSale\Services\AnalyticsService;
use XRPLSale\Services\WebhooksService;

/**
 * XRPL.Sale SDK Client
 * 
 * Main client for interacting with the XRPL.Sale platform API
 * 
 * @property AuthService $auth
 * @property ProjectsService $projects
 * @property InvestmentsService $investments
 * @property AnalyticsService $analytics
 * @property WebhooksService $webhooks
 */
class XRPLSaleClient
{
    public const VERSION = '1.0.0';
    public const DEFAULT_TIMEOUT = 30;
    public const DEFAULT_MAX_RETRIES = 3;
    
    private ClientInterface $httpClient;
    private array $config;
    private ?string $authToken = null;
    
    public AuthService $auth;
    public ProjectsService $projects;
    public InvestmentsService $investments;
    public AnalyticsService $analytics;
    public WebhooksService $webhooks;
    
    /**
     * Create a new XRPL.Sale client instance
     * 
     * @param string|array $config API key or configuration array
     * @param ClientInterface|null $httpClient Optional HTTP client
     */
    public function __construct($config, ?ClientInterface $httpClient = null)
    {
        // Normalize configuration
        if (is_string($config)) {
            $config = ['api_key' => $config];
        }
        
        $this->config = array_merge([
            'api_key' => null,
            'environment' => 'production',
            'base_url' => null,
            'timeout' => self::DEFAULT_TIMEOUT,
            'max_retries' => self::DEFAULT_MAX_RETRIES,
            'retry_delay' => 1.0,
            'webhook_secret' => null,
            'debug' => false,
        ], $config);
        
        // Set base URL based on environment
        if (!$this->config['base_url']) {
            $this->config['base_url'] = $this->config['environment'] === 'testnet'
                ? 'https://api-testnet.xrpl.sale/v1'
                : 'https://api.xrpl.sale/v1';
        }
        
        // Initialize HTTP client
        $this->httpClient = $httpClient ?? $this->createHttpClient();
        
        // Initialize services
        $this->initializeServices();
    }
    
    /**
     * Create the default HTTP client with retry middleware
     */
    private function createHttpClient(): ClientInterface
    {
        $stack = HandlerStack::create();
        
        // Add retry middleware
        $stack->push(Middleware::retry(
            function ($retries, $request, $response, $exception) {
                // Retry on network errors or 5xx responses
                if ($retries >= $this->config['max_retries']) {
                    return false;
                }
                
                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }
                
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }
                
                return false;
            },
            function ($retries) {
                // Exponential backoff
                return (int) ($this->config['retry_delay'] * 1000 * pow(2, $retries));
            }
        ));
        
        // Add debug middleware if enabled
        if ($this->config['debug']) {
            $stack->push(Middleware::log(
                new \Psr\Log\NullLogger(),
                new \GuzzleHttp\MessageFormatter()
            ));
        }
        
        return new HttpClient([
            'base_uri' => $this->config['base_url'],
            'timeout' => $this->config['timeout'],
            'handler' => $stack,
            'headers' => [
                'User-Agent' => 'XRPL.Sale-PHP-SDK/' . self::VERSION,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }
    
    /**
     * Initialize service instances
     */
    private function initializeServices(): void
    {
        $this->auth = new AuthService($this);
        $this->projects = new ProjectsService($this);
        $this->investments = new InvestmentsService($this);
        $this->analytics = new AnalyticsService($this);
        $this->webhooks = new WebhooksService($this);
    }
    
    /**
     * Make an authenticated API request
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array Response data
     * @throws XRPLSaleException
     */
    public function request(string $method, string $endpoint, array $options = []): array
    {
        // Add authentication header
        if ($this->authToken) {
            $options['headers']['Authorization'] = 'Bearer ' . $this->authToken;
        } elseif ($this->config['api_key']) {
            $options['headers']['X-API-Key'] = $this->config['api_key'];
        }
        
        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleException($e);
        }
    }
    
    /**
     * Handle API response
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        // Try to decode JSON
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new XRPLSaleException('Invalid JSON response: ' . $body, $statusCode);
        }
        
        // Check for error responses
        if ($statusCode >= 400) {
            $this->handleErrorResponse($data, $statusCode);
        }
        
        return $data;
    }
    
    /**
     * Handle error responses
     */
    private function handleErrorResponse(array $data, int $statusCode): void
    {
        $message = $data['error']['message'] ?? $data['message'] ?? 'Unknown error';
        $code = $data['error']['code'] ?? $data['code'] ?? null;
        
        switch ($statusCode) {
            case 400:
                throw new ValidationException($message, $code, $data['details'] ?? []);
            case 401:
                throw new AuthenticationException($message, $code);
            case 404:
                throw new NotFoundException($message, $code);
            case 429:
                throw new RateLimitException($message, $code);
            default:
                throw new XRPLSaleException($message, $statusCode, $code);
        }
    }
    
    /**
     * Handle Guzzle exceptions
     */
    private function handleException(GuzzleException $e): XRPLSaleException
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            
            $data = json_decode($body, true) ?? [];
            $this->handleErrorResponse($data, $statusCode);
        }
        
        return new XRPLSaleException(
            'Request failed: ' . $e->getMessage(),
            $e->getCode()
        );
    }
    
    /**
     * Set authentication token
     */
    public function setAuthToken(string $token): void
    {
        $this->authToken = $token;
    }
    
    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Get webhook secret for signature verification
     */
    public function getWebhookSecret(): ?string
    {
        return $this->config['webhook_secret'];
    }
    
    // Convenience methods for HTTP verbs
    
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }
    
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }
    
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }
    
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('PATCH', $endpoint, ['json' => $data]);
    }
    
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }
}