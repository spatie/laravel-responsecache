<?php

namespace Spatie\ResponseCache\Support;

use ReflectionClass;
use ReflectionMethod;

class AttributeReader
{
    /**
     * Get the first matching attribute from a controller action.
     *
     * @param  string  $action  The controller action in "Controller@method" format
     * @param  array  $attributeClasses  Array of attribute class names to search for
     * @return object|null  The first matching attribute instance or null if none found
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

        try {
            $reflectionClass = new ReflectionClass($controller);
            $reflectionMethod = $reflectionClass->getMethod($method);

            // Check method-level attributes first (they take precedence)
            $attribute = static::findAttributeInReflection($reflectionMethod, $attributeClasses);
            if ($attribute) {
                return $attribute;
            }

            // Then check class-level attributes
            return static::findAttributeInReflection($reflectionClass, $attributeClasses);
        } catch (\ReflectionException) {
            return null;
        }
    }

    protected static function parseAction(string $action): array
    {
        if (! str_contains($action, '@')) {
            return [null, null];
        }

        return explode('@', $action);
    }

    protected static function findAttributeInReflection(
        ReflectionClass|ReflectionMethod $reflection,
        array $attributeClasses
    ): ?object {
        foreach ($attributeClasses as $attributeClass) {
            $attributes = $reflection->getAttributes($attributeClass);

            if (! empty($attributes)) {
                return $attributes[0]->newInstance();
            }
        }

        return null;
    }
}
