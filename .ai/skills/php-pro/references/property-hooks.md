# Property Hooks (PHP 8.4)

Property hooks allow you to intercept property access and mutation directly in the class definition.

## Basic Syntax
```php
class User
{
    public string $name {
        get => ucfirst($this->name);
        set(string $value) => strtolower($value);
    }
}
```

## Virtual Properties
Properties that don't have a backing value in the database.
```php
class User extends Model
{
    public string $fullName {
        get => "{$this->first_name} {$this->last_name}";
    }
}
```

## Advantages
1. **Type Safety:** Hooks respect the property type.
2. **Readability:** Logic is right next to the property.
3. **Performance:** Native PHP implementation is faster than Laravel's `Attribute` class.
