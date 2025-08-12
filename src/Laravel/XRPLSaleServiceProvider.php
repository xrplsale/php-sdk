<?php

namespace XRPLSale\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use XRPLSale\XRPLSaleClient;

/**
 * Laravel Service Provider for XRPL.Sale SDK
 */
class XRPLSaleServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/xrplsale.php',
            'xrplsale'
        );
        
        // Register the client as a singleton
        $this->app->singleton(XRPLSaleClient::class, function ($app) {
            $config = $app['config']['xrplsale'];
            
            return new XRPLSaleClient([
                'api_key' => $config['api_key'] ?? env('XRPLSALE_API_KEY'),
                'environment' => $config['environment'] ?? env('XRPLSALE_ENVIRONMENT', 'production'),
                'webhook_secret' => $config['webhook_secret'] ?? env('XRPLSALE_WEBHOOK_SECRET'),
                'timeout' => $config['timeout'] ?? 30,
                'max_retries' => $config['max_retries'] ?? 3,
                'debug' => $config['debug'] ?? env('XRPLSALE_DEBUG', false),
            ]);
        });
        
        // Register alias
        $this->app->alias(XRPLSaleClient::class, 'xrplsale');
    }
    
    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/xrplsale.php' => config_path('xrplsale.php'),
            ], 'config');
            
            // Publish migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'migrations');
        }
        
        // Register webhook routes macro
        Route::macro('xrplsaleWebhooks', function ($url = 'webhooks/xrplsale') {
            return Route::post($url, [WebhookController::class, 'handle'])
                ->name('xrplsale.webhooks');
        });
        
        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('xrplsale.webhook', WebhookMiddleware::class);
    }
    
    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            XRPLSaleClient::class,
            'xrplsale',
        ];
    }
}