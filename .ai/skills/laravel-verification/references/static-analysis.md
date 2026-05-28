# PHPStan & Code Quality

## Level 9 Compatibility
- Avoid `mixed`.
- Type hint array shapes if possible.
- Document closure return types for transactions.

## Common Fixes
- `getData(true)` check:
```php
$data = $response->getData(true);
if (is_array($data)) {
    // Access keys safely
}
```
