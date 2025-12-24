# Architecture

## Directory Structure

```
app/
├── Actions/           # Single-responsibility business logic
├── DataObjects/       # Type-safe DTOs (spatie/laravel-data)
├── Enums/             # Type-safe constants
├── Filament/          # Admin panel resources
├── Http/
│   ├── Controllers/   # Minimal - most logic in Actions
│   └── Requests/      # Form validation
├── Models/            # Eloquent models
├── Services/          # External API clients
└── Support/           # Utilities, traits
```

## Design Principles

### Actions for Business Logic

All business logic lives in `app/Actions/`. Each Action has a single responsibility and a static `run()` method:

```php
class CreateUser
{
    public static function run(CreateUserData $data): User
    {
        return DB::transaction(fn () => User::create($data->toArray()));
    }
}
```

### DTOs for Data Transfer

Use `spatie/laravel-data` for type-safe data objects:

```php
class CreateUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
    ) {}
}
```

### Thin Controllers

Controllers should only:
1. Validate input (via Form Requests)
2. Call an Action
3. Return a response

```php
public function store(StoreUserRequest $request): RedirectResponse
{
    $user = CreateUser::run(CreateUserData::from($request));

    return redirect()->route('users.show', $user);
}
```

## Testing Strategy

- **Feature tests** for HTTP endpoints and integrations
- **Unit tests** for Actions and complex logic
- Use factories for model creation
- Test happy path, failure path, and edge cases
