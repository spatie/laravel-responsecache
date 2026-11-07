<?php

namespace Spatie\ResponseCache\Support;

use Spatie\Attributes\Attributes;

class AttributeReader
{
    protected static array $resolved = [];

    /**
     * Get the first matching attribute from a controller action.
     *
     * @param  string  $action  The controller action in "Controller@method" format, or an invokable controller class name
     * @param  array  $attributeClasses  Array of attribute class names to search for
     * @return object|null The first matching attribute instance or null if none found
     */
    public static function getFirstAttribute(string $action, array $attributeClasses): ?object
    {
        $key = $action.'|'.implode(',', $attributeClasses);

        if (! array_key_exists($key, static::$resolved)) {
            static::$resolved[$key] = static::resolveFirstAttribute($action, $attributeClasses);
        }

        $attribute = static::$resolved[$key];

        return $attribute ? clone $attribute : null;
    }

    protected static function resolveFirstAttribute(string $action, array $attributeClasses): ?object
    {
        [$controller, $method] = static::parseAction($action);

        if (! $controller || ! $method) {
            return null;
        }

        if (! class_exists($controller)) {
            return null;
        }

        // Check method-level attributes first (they take precedence)
        foreach ($attributeClasses as $attributeClass) {
            $attribute = Attributes::onMethod($controller, $method, $attributeClass);

            if ($attribute) {
                return $attribute;
            }
        }

        // Then check class-level attributes
        foreach ($attributeClasses as $attributeClass) {
            $attribute = Attributes::get($controller, $attributeClass);

            if ($attribute) {
                return $attribute;
            }
        }

        return null;
    }

    protected static function parseAction(string $action): array
    {
        // An invokable controller can be registered without a method: Route::get('/', MyController::class)
        if (! str_contains($action, '@')) {
            return [$action, '__invoke'];
        }

        return explode('@', $action);
    }
}
