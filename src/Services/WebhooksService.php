<?php

namespace XRPLSale\Services;

use XRPLSale\XRPLSaleClient;
use XRPLSale\Models\WebhookEvent;
use XRPLSale\Exceptions\WebhookException;

/**
 * Webhooks Service
 * 
 * Handle webhook verification and management for real-time events
 */
class WebhooksService
{
    private XRPLSaleClient $client;
    
    public function __construct(XRPLSaleClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Verify webhook signature
     * 
     * @param string $payload Raw webhook payload
     * @param string $signature Signature from header
     * @param string|null $secret Optional webhook secret
     * @return bool
     */
    public function verifySignature(string $payload, string $signature, ?string $secret = null): bool
    {
        $secret = $secret ?? $this->client->getWebhookSecret();
        
        if (!$secret) {
            throw new WebhookException('Webhook secret is required for signature verification');
        }
        
        // Calculate expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        // Use timing-safe comparison
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Parse webhook payload
     * 
     * @param string $payload JSON webhook payload
     * @return WebhookEvent
     * @throws WebhookException
     */
    public function parseWebhook(string $payload): WebhookEvent
    {
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WebhookException('Invalid webhook payload: ' . json_last_error_msg());
        }
        
        return new WebhookEvent($data);
    }
    
    /**
     * Laravel middleware for webhook handling
     * 
     * @param bool $verifySignature Whether to verify signature
     * @param string|null $secret Optional webhook secret
     * @return \Closure
     */
    public function laravelMiddleware(bool $verifySignature = true, ?string $secret = null): \Closure
    {
        return function ($request, $next) use ($verifySignature, $secret) {
            if ($verifySignature) {
                $signature = $request->header('X-XRPL-Sale-Signature');
                $payload = $request->getContent();
                
                if (!$signature || !$this->verifySignature($payload, $signature, $secret)) {
                    abort(401, 'Invalid webhook signature');
                }
            }
            
            // Parse and attach event to request
            $event = $this->parseWebhook($request->getContent());
            $request->merge(['webhook_event' => $event]);
            
            return $next($request);
        };
    }
    
    /**
     * Symfony event subscriber for webhook handling
     * 
     * @param bool $verifySignature Whether to verify signature
     * @param string|null $secret Optional webhook secret
     * @return WebhookSubscriber
     */
    public function symfonySubscriber(bool $verifySignature = true, ?string $secret = null): WebhookSubscriber
    {
        return new WebhookSubscriber($this, $verifySignature, $secret);
    }
    
    /**
     * Register a webhook endpoint
     * 
     * @param array $webhookData Webhook configuration
     * @return array
     */
    public function register(array $webhookData): array
    {
        return $this->client->post('/webhooks', $webhookData);
    }
    
    /**
     * List registered webhooks
     * 
     * @return array
     */
    public function list(): array
    {
        return $this->client->get('/webhooks');
    }
    
    /**
     * Get a specific webhook
     * 
     * @param string $webhookId Webhook ID
     * @return array
     */
    public function get(string $webhookId): array
    {
        return $this->client->get("/webhooks/{$webhookId}");
    }
    
    /**
     * Update a webhook
     * 
     * @param string $webhookId Webhook ID
     * @param array $updates Update data
     * @return array
     */
    public function update(string $webhookId, array $updates): array
    {
        return $this->client->patch("/webhooks/{$webhookId}", $updates);
    }
    
    /**
     * Delete a webhook
     * 
     * @param string $webhookId Webhook ID
     * @return array
     */
    public function delete(string $webhookId): array
    {
        return $this->client->delete("/webhooks/{$webhookId}");
    }
    
    /**
     * Test webhook delivery
     * 
     * @param string $webhookId Webhook ID
     * @return array
     */
    public function test(string $webhookId): array
    {
        return $this->client->post("/webhooks/{$webhookId}/test");
    }
    
    /**
     * Get webhook delivery logs
     * 
     * @param string $webhookId Webhook ID
     * @param array $options Filter options
     * @return array
     */
    public function getDeliveries(string $webhookId, array $options = []): array
    {
        return $this->client->get("/webhooks/{$webhookId}/deliveries", $options);
    }
    
    /**
     * Retry a failed webhook delivery
     * 
     * @param string $webhookId Webhook ID
     * @param string $deliveryId Delivery ID
     * @return array
     */
    public function retryDelivery(string $webhookId, string $deliveryId): array
    {
        return $this->client->post("/webhooks/{$webhookId}/deliveries/{$deliveryId}/retry");
    }
}

/**
 * Symfony Event Subscriber for webhooks
 */
class WebhookSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    private WebhooksService $service;
    private bool $verifySignature;
    private ?string $secret;
    
    public function __construct(WebhooksService $service, bool $verifySignature = true, ?string $secret = null)
    {
        $this->service = $service;
        $this->verifySignature = $verifySignature;
        $this->secret = $secret;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            \Symfony\Component\HttpKernel\KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
    
    public function onKernelRequest(\Symfony\Component\HttpKernel\Event\RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only process webhook endpoints
        if (!str_starts_with($request->getPathInfo(), '/webhooks')) {
            return;
        }
        
        if ($this->verifySignature) {
            $signature = $request->headers->get('X-XRPL-Sale-Signature');
            $payload = $request->getContent();
            
            if (!$signature || !$this->service->verifySignature($payload, $signature, $this->secret)) {
                throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('Invalid webhook signature');
            }
        }
        
        // Parse and attach event to request
        $event = $this->service->parseWebhook($request->getContent());
        $request->attributes->set('webhook_event', $event);
    }
}