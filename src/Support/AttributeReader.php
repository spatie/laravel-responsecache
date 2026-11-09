<?php

namespace Spatie\ResponseCache\Support;

use ReflectionAttribute;
use ReflectionClass;

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
        [$controller, $method] = static::parseAction($action);

        if (! $controller || ! $method || ! class_exists($controller)) {
            return null;
        }

        $key = static::class.'|'.$action.'|'.implode(',', $attributeClasses);

        if (! array_key_exists($key, static::$resolved)) {
            static::$resolved[$key] = static::resolveFirstAttribute($controller, $method, $attributeClasses);
        }

        return static::$resolved[$key]?->newInstance();
    }

    protected static function resolveFirstAttribute(string $controller, string $method, array $attributeClasses): ?ReflectionAttribute
    {
        $reflection = new ReflectionClass($controller);

        // Check method-level attributes first (they take precedence)
        if ($reflection->hasMethod($method)) {
            foreach ($attributeClasses as $attributeClass) {
                $attributes = $reflection->getMethod($method)->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

                if ($attributes !== []) {
                    return $attributes[0];
                }
            }
        }

        // Then check class-level attributes
        foreach ($attributeClasses as $attributeClass) {
            $attributes = $reflection->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

            if ($attributes !== []) {
                return $attributes[0];
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
