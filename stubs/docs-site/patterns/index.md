# Code Patterns

This project uses consistent patterns to keep code maintainable and testable.

## Overview

| Pattern | Location | Purpose |
|---------|----------|---------|
| [Actions](/patterns/actions) | `app/Actions/` | Business logic |
| [DTOs](/patterns/dtos) | `app/DataObjects/` | Type-safe data transfer |
| [Services](/patterns/services) | `app/Services/` | External integrations |

## When to Use What

### Actions
- Creating, updating, or deleting resources
- Complex operations with multiple steps
- Anything that needs transaction wrapping
- Logic that might be called from multiple places

### DTOs
- Passing data between layers
- Validating and transforming input
- API responses

### Services
- External API clients
- Third-party integrations
- Complex helper classes
