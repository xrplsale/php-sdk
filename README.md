# XRPL.Sale PHP SDK

Official PHP SDK for integrating with the XRPL.Sale platform - the native XRPL launchpad for token sales and project funding.

[![Latest Version](https://img.shields.io/packagist/v/xrplsale/php-sdk.svg?style=flat-square)](https://packagist.org/packages/xrplsale/php-sdk)
[![PHP Version](https://img.shields.io/packagist/php-v/xrplsale/php-sdk.svg?style=flat-square)](https://packagist.org/packages/xrplsale/php-sdk)
[![License](https://img.shields.io/packagist/l/xrplsale/php-sdk.svg?style=flat-square)](LICENSE)

## Features

- 🐘 **Modern PHP 8.0+** - Built with latest PHP features and type declarations
- 🚀 **PSR Compliant** - Follows PSR-4, PSR-7, PSR-12, and PSR-18 standards
- 🔐 **XRPL Wallet Authentication** - Seamless wallet integration
- 📊 **Project Management** - Create, launch, and manage token sales
- 💰 **Investment Tracking** - Monitor investments and analytics
- 🔔 **Webhook Support** - Real-time event notifications with signature verification
- 🎯 **Laravel Integration** - Service provider, facade, and middleware
- 🎼 **Symfony Integration** - Bundle and event subscriber support
- 🛡️ **Error Handling** - Comprehensive exception hierarchy
- 🔄 **Auto-retry Logic** - Resilient API calls with exponential backoff

## Installation

Install via Composer:

```bash
composer require xrplsale/php-sdk
```

### Laravel Installation

The package will auto-register the service provider. Publish the configuration:

```bash
php artisan vendor:publish --provider="XRPLSale\Laravel\XRPLSaleServiceProvider"
```

Add your credentials to `.env`:

```env
XRPLSALE_API_KEY=your-api-key
XRPLSALE_ENVIRONMENT=production
XRPLSALE_WEBHOOK_SECRET=your-webhook-secret
```

### Symfony Installation

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    XRPLSale\Symfony\XRPLSaleBundle::class => ['all' => true],
];
```

Configure in `config/packages/xrplsale.yaml`:

```yaml
xrplsale:
    api_key: '%env(XRPLSALE_API_KEY)%'
    environment: '%env(XRPLSALE_ENVIRONMENT)%'
    webhook_secret: '%env(XRPLSALE_WEBHOOK_SECRET)%'
```

## Quick Start

### Basic Usage

```php
use XRPLSale\XRPLSaleClient;

// Initialize the client
$client = new XRPLSaleClient([
    'api_key' => 'your-api-key',
    'environment' => 'production', // or 'testnet'
    'debug' => true
]);

// Create a new project
$project = $client->projects->create([
    'name' => 'My DeFi Protocol',
    'description' => 'Revolutionary DeFi protocol on XRPL',
    'token_symbol' => 'MDP',
    'total_supply' => '100000000',
    'tiers' => [
        [
            'tier' => 1,
            'price_per_token' => '0.001',
            'total_tokens' => '20000000'
        ]
    ],
    'sale_start_date' => '2025-02-01T00:00:00Z',
    'sale_end_date' => '2025-03-01T00:00:00Z'
]);

echo "Project created: {$project->id}\n";
```

### Laravel Usage

```php
use XRPLSale\Laravel\Facades\XRPLSale;

// Using the facade
$projects = XRPLSale::projects()->getActive();

// Using dependency injection
class ProjectController extends Controller
{
    public function __construct(
        private XRPLSaleClient $xrplSale
    ) {}
    
    public function index()
    {
        $projects = $this->xrplSale->projects->list([
            'status' => 'active',
            'page' => 1,
            'limit' => 10
        ]);
        
        return view('projects.index', compact('projects'));
    }
}
```

### Symfony Usage

```php
use XRPLSale\XRPLSaleClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProjectController extends AbstractController
{
    public function __construct(
        private XRPLSaleClient $xrplSale
    ) {}
    
    #[Route('/projects', name: 'app_projects')]
    public function index(): Response
    {
        $projects = $this->xrplSale->projects->getActive();
        
        return $this->render('projects/index.html.twig', [
            'projects' => $projects,
        ]);
    }
}
```

## Authentication

### XRPL Wallet Authentication

```php
// Generate authentication challenge
$challenge = $client->auth->generateChallenge('rYourWalletAddress...');

// Sign the challenge with your wallet
// (implementation depends on your wallet library)
$signature = signMessage($challenge['challenge']);

// Authenticate
$authResult = $client->auth->authenticate([
    'wallet_address' => 'rYourWalletAddress...',
    'signature' => $signature,
    'timestamp' => $challenge['timestamp']
]);

echo "Authentication successful: {$authResult->token}\n";

// Set the auth token for subsequent requests
$client->setAuthToken($authResult->token);
```

## Core Services

### Projects Service

```php
// List active projects
$projects = $client->projects->getActive(page: 1, limit: 10);

// Get project details
$project = $client->projects->get('proj_abc123');

// Launch a project
$client->projects->launch('proj_abc123');

// Get project statistics
$stats = $client->projects->getStats('proj_abc123');
echo "Total raised: {$stats['total_raised']} XRP\n";

// Search projects
$results = $client->projects->search('DeFi', [
    'status' => 'active',
    'sort_by' => 'popularity'
]);

// Get trending projects
$trending = $client->projects->getTrending('24h', limit: 5);
```

### Investments Service

```php
// Create an investment
$investment = $client->investments->create([
    'project_id' => 'proj_abc123',
    'amount_xrp' => '100',
    'investor_account' => 'rInvestorAddress...'
]);

// List investments for a project
$investments = $client->investments->getByProject('proj_abc123');

// Get investor summary
$summary = $client->investments->getInvestorSummary('rInvestorAddress...');

// Simulate an investment
$simulation = $client->investments->simulate([
    'project_id' => 'proj_abc123',
    'amount_xrp' => '100'
]);
echo "Expected tokens: {$simulation['token_amount']}\n";
```

### Analytics Service

```php
// Get platform analytics
$analytics = $client->analytics->getPlatformAnalytics();
echo "Total raised: {$analytics['total_raised_xrp']} XRP\n";

// Get project-specific analytics
$projectAnalytics = $client->analytics->getProjectAnalytics('proj_abc123', [
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31'
]);

// Get market trends
$trends = $client->analytics->getMarketTrends('30d');

// Export data
$export = $client->analytics->exportData([
    'type' => 'projects',
    'format' => 'csv',
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31'
]);
echo "Download URL: {$export['download_url']}\n";
```

## Webhook Integration

### Laravel Webhook Handling

```php
// routes/web.php
Route::xrplsaleWebhooks('webhooks/xrplsale');

// app/Http/Controllers/WebhookController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use XRPLSale\Models\WebhookEvent;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->get('webhook_event');
        
        switch ($event->type) {
            case 'investment.created':
                $this->handleNewInvestment($event->data);
                break;
            case 'project.launched':
                $this->handleProjectLaunched($event->data);
                break;
            case 'tier.completed':
                $this->handleTierCompleted($event->data);
                break;
        }
        
        return response('OK', 200);
    }
    
    private function handleNewInvestment(array $data): void
    {
        // Process new investment
        \Log::info('New investment', $data);
        
        // Send notification email
        \Mail::to($data['investor_email'])->send(
            new InvestmentConfirmation($data)
        );
    }
}
```

### Symfony Webhook Handling

```php
// src/Controller/WebhookController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use XRPLSale\Models\WebhookEvent;

class WebhookController extends AbstractController
{
    #[Route('/webhooks/xrplsale', name: 'xrplsale_webhooks', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        /** @var WebhookEvent $event */
        $event = $request->attributes->get('webhook_event');
        
        switch ($event->type) {
            case 'investment.created':
                $this->handleNewInvestment($event->data);
                break;
            case 'project.launched':
                $this->handleProjectLaunched($event->data);
                break;
        }
        
        return new Response('OK', 200);
    }
    
    private function handleNewInvestment(array $data): void
    {
        // Process the investment
        $this->logger->info('New investment received', $data);
    }
}
```

### Manual Webhook Verification

```php
$signature = $_SERVER['HTTP_X_XRPL_SALE_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');

if ($client->webhooks->verifySignature($payload, $signature)) {
    $event = $client->webhooks->parseWebhook($payload);
    // Process event...
} else {
    http_response_code(401);
    echo 'Invalid signature';
    exit;
}
```

## Error Handling

```php
use XRPLSale\Exceptions\{
    XRPLSaleException,
    ValidationException,
    AuthenticationException,
    NotFoundException,
    RateLimitException
};

try {
    $project = $client->projects->get('invalid-id');
} catch (NotFoundException $e) {
    echo "Project not found\n";
} catch (AuthenticationException $e) {
    echo "Authentication failed: {$e->getMessage()}\n";
} catch (ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
    print_r($e->getDetails());
} catch (RateLimitException $e) {
    echo "Rate limit exceeded. Retry after: {$e->getRetryAfter()} seconds\n";
} catch (XRPLSaleException $e) {
    echo "API error: {$e->getMessage()} (Code: {$e->getCode()})\n";
}
```

## Configuration Options

```php
$client = new XRPLSaleClient([
    'api_key' => 'your-api-key',              // Required
    'environment' => 'production',            // 'production' or 'testnet'
    'base_url' => null,                       // Custom API URL (optional)
    'timeout' => 30,                          // Request timeout in seconds
    'max_retries' => 3,                       // Maximum retry attempts
    'retry_delay' => 1.0,                     // Base delay between retries
    'webhook_secret' => 'your-secret',        // For webhook verification
    'debug' => false,                         // Enable debug logging
]);
```

## Pagination

```php
$response = $client->projects->list([
    'page' => 1,
    'limit' => 50,
    'sort_by' => 'created_at',
    'sort_order' => 'desc'
]);

foreach ($response->data as $project) {
    echo "Project: {$project->name}\n";
}

echo "Page {$response->pagination->page} of {$response->pagination->total_pages}\n";
echo "Total projects: {$response->pagination->total}\n";
```

## Testing

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage

# Static analysis
vendor/bin/phpstan analyse

# Code style check
vendor/bin/phpcs

# Fix code style
vendor/bin/phpcbf
```

## Development

```bash
# Clone the repository
git clone https://github.com/xrplsale/php-sdk.git
cd php-sdk

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Check code style
composer check-style

# Fix code style
composer fix-style
```

## Support

- 📖 [Documentation](https://docs.xrpl.sale)
- 💬 [Discord Community](https://discord.gg/xrpl-sale)
- 🐛 [Issue Tracker](https://github.com/xrplsale/php-sdk/issues)
- 📧 [Email Support](mailto:developers@xrpl.sale)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Links

- [XRPL.Sale Platform](https://xrpl.sale)
- [API Documentation](https://docs.xrpl.sale/api)
- [Other SDKs](https://docs.xrpl.sale/developers/sdk-downloads)
- [GitHub Organization](https://github.com/xrplsale)

---

Made with ❤️ by the XRPL.Sale team