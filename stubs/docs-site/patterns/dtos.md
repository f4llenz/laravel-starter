# Data Transfer Objects (DTOs)

DTOs provide type-safe data containers using `spatie/laravel-data`.

## Basic DTO

```php
<?php

namespace App\DataObjects;

use Spatie\LaravelData\Data;

class CreateUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
    ) {}
}
```

## Creating DTOs

### From Array
```php
$data = CreateUserData::from([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

### From Request
```php
$data = CreateUserData::from($request);
```

### From Model
```php
$data = UserData::from($user);
```

## Validation

DTOs can include validation rules:

```php
class CreateUserData extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public string $name,

        #[Required, Email, Unique('users', 'email')]
        public string $email,
    ) {}
}
```

## Transformation

Transform data on the way in or out:

```php
class CreateUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: trim($request->name),
            email: strtolower($request->email),
        );
    }
}
```

## Nested DTOs

```php
class OrderData extends Data
{
    public function __construct(
        public CustomerData $customer,
        /** @var LineItemData[] */
        public array $items,
    ) {}
}
```

## Collections

```php
// Collection of DTOs
$users = UserData::collect(User::all());
```
