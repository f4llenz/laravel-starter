# Services

Services handle external integrations and complex helper functionality.

## When to Use Services

- External API clients
- Third-party integrations
- Complex operations that don't fit the Action pattern
- Stateful helpers that need dependency injection

## Structure

```php
<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PaymentGateway
{
    protected PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::baseUrl(config('services.payment.url'))
            ->withToken(config('services.payment.key'))
            ->timeout(30);
    }

    public function charge(int $amount, string $token): PaymentResult
    {
        $response = $this->client->post('/charges', [
            'amount' => $amount,
            'token' => $token,
        ]);

        return new PaymentResult($response->json());
    }
}
```

## Dependency Injection

Register services in a provider or use auto-wiring:

```php
// In a controller
public function __construct(
    private PaymentGateway $payments
) {}

// Or resolve from container
$gateway = app(PaymentGateway::class);
```

## Interface Pattern

For swappable implementations:

```php
// Interface
interface PaymentGatewayInterface
{
    public function charge(int $amount, string $token): PaymentResult;
}

// Implementation
class StripeGateway implements PaymentGatewayInterface
{
    // ...
}

// Binding
$this->app->bind(PaymentGatewayInterface::class, StripeGateway::class);
```

## Testing Services

```php
it('charges a card', function () {
    Http::fake([
        'payment.test/charges' => Http::response(['id' => 'ch_123']),
    ]);

    $gateway = new PaymentGateway();
    $result = $gateway->charge(1000, 'tok_visa');

    expect($result->id)->toBe('ch_123');
});
```
