<?php

namespace Spatie\ResponseCache\Support;

use Spatie\Attributes\Attributes;

class AttributeReader
{
    /**
     * Get the first matching attribute from a controller action.
     *
     * @param  string  $action  The controller action in "Controller@method" format
     * @param  array  $attributeClasses  Array of attribute class names to search for
     * @return object|null The first matching attribute instance or null if none found
     */
    public static function getFirstAttribute(string $action, array $attributeClasses): ?object
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
        if (! str_contains($action, '@')) {
            return [null, null];
        }

        return explode('@', $action);
    }
}
