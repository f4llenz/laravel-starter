# Actions

Actions encapsulate single units of business logic. They're the primary way to organize domain operations in this project.

## Structure

```php
<?php

namespace App\Actions;

use App\DataObjects\CreateUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    public static function run(CreateUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
            ]);

            // Additional logic...

            return $user;
        });
    }
}
```

## Guidelines

### Single Responsibility
Each Action does one thing. If you find yourself adding "and" to describe it, split it up.

```php
// Good
CreateUser::run($data);
SendWelcomeEmail::run($user);

// Bad
CreateUserAndSendWelcomeEmail::run($data);
```

### Use DTOs for Input
Always accept a DTO rather than loose parameters:

```php
// Good
public static function run(CreateUserData $data): User

// Avoid
public static function run(string $name, string $email, ?string $phone): User
```

### Wrap in Transactions
If the Action modifies multiple records, wrap in a transaction:

```php
return DB::transaction(function () use ($data) {
    // Multiple database operations
});
```

### Return the Result
Always return the created/modified resource:

```php
public static function run(CreateUserData $data): User
{
    $user = User::create(...);
    return $user; // Don't just return void
}
```

## Testing Actions

```php
it('creates a user', function () {
    $data = CreateUserData::from([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $user = CreateUser::run($data);

    expect($user)
        ->name->toBe('John Doe')
        ->email->toBe('john@example.com');
});
```
