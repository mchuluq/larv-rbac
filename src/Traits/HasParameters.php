<?php 

declare(strict_types=1);

namespace Mchuluq\Larv\Rbac\Traits;

use function assert;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use function is_string;
use ReflectionMethod;
use ReflectionParameter;
use TypeError;

trait HasParameters{

    public static function with($arguments): string{
        $arguments = new Collection($arguments);
        $parameters = static::parameters();
        static::validateArgumentMap($parameters, $arguments);
        $arguments = static::parseArgumentMap($parameters, new Collection($arguments));
        return static::formatArguments($arguments);
    }

    public static function in($arguments): string{
        $arguments = new Collection($arguments);
        $parameters = static::parameters();
        static::validateArgumentList($parameters, $arguments);
        $arguments = static::parseArgumentList($arguments);
        return static::formatArguments($arguments);
    }

    private static function formatArguments(Collection $arguments): string{
        if ($arguments->isEmpty()) {
            return static::class;
        }
        return static::class . ':' . $arguments->implode(',');
    }

    private static function parseArgumentList(Collection $arguments): Collection{
        return $arguments->map(
            static function ($argument): string {
                return static::castToString($argument);
            }
        );
    }

    private static function parseArgumentMap(Collection $parameters, Collection $arguments): Collection{
        return $parameters->map(static function (ReflectionParameter $parameter) use ($arguments): ?string {
            if ($parameter->isVariadic()) {
                return static::parseVariadicArgument($parameter, $arguments);
            }
            return static::parseStandardArgument($parameter, $arguments);
        })->reject(static function (?string $argument): bool {
            return $argument === null;
        });
    }

    private static function parseVariadicArgument(ReflectionParameter $parameter, Collection $arguments): ?string{
        if (!$arguments->has($parameter->getName())) {
            return null;
        }
        $values = new Collection($arguments->get($parameter->getName()));
        if ($values->isEmpty()) {
            return null;
        }
        return $values->map(
            static function ($value) {
                return static::castToString($value);
            }
        )->implode(',');
    }

    private static function parseStandardArgument(ReflectionParameter $parameter, Collection $arguments): string{
        if ($arguments->has($parameter->getName())) {
            return static::castToString($arguments->get($parameter->getName()));
        }
        return static::castToString($parameter->getDefaultValue());
    }

    private static function parameters(): Collection{
        $handle = new ReflectionMethod(static::class, 'handle');
        return Collection::make($handle->getParameters())
            ->slice(2)
            ->keyBy(static function (ReflectionParameter $parameter): string {
                return $parameter->getName();
            });
    }

    private static function castToString($value): string{
        if ($value === false) {
            return '0';
        }
        return (string) $value;
    }

    private static function validateArgumentList(Collection $parameters, Collection $arguments): void{
        static::validateArgumentListIsNotAnAssociativeArray($arguments);
        static::validateParametersAreOptional($parameters->slice($arguments->count()));
    }

    private static function validateArgumentMap(Collection $parameters, Collection $arguments): void{
        static::validateArgumentMapIsAnAssociativeArray($arguments);
        static::validateNoUnexpectedArguments($parameters, $arguments);
        static::validateParametersAreOptional($parameters->diffKeys($arguments));
    }

    private static function validateParametersAreOptional(Collection $parameters): void{
        $missingRequiredParameter = $parameters->reject(static function (ReflectionParameter $parameter): bool {
            return $parameter->isDefaultValueAvailable() || $parameter->isVariadic();
        })->first();
        if ($missingRequiredParameter === null) {
            return;
        }
        assert($missingRequiredParameter instanceof ReflectionParameter);
        throw new TypeError('Missing required argument $' . $missingRequiredParameter->getName() . ' for middleware ' . static::class . '::handle()');
    }

    private static function validateArgumentListIsNotAnAssociativeArray(Collection $arguments): void{
        if (Arr::isAssoc($arguments->all())) {
            throw new TypeError('Expected a non-associative array in HasParameters::in() but received an associative array. You should use the HasParameters::with() method instead.');
        }
    }

    private static function validateArgumentMapIsAnAssociativeArray(Collection $arguments): void{
        if ($arguments->isNotEmpty() && !Arr::isAssoc($arguments->all())) {
            throw new TypeError('Expected an associative array in HasParameters::with() but received a non-associative array. You should use the HasParameters::in() method instead.');
        }
    }

    private static function validateNoUnexpectedArguments(Collection $parameters, Collection $arguments): void{
        $unexpectedArgument = $arguments->keys()
            ->first(static function (string $name) use ($parameters): bool {
                return !$parameters->has($name);
            });
        if ($unexpectedArgument === null) {
            return;
        }
        assert(is_string($unexpectedArgument));
        throw new TypeError('Unknown argument $' . $unexpectedArgument . ' passed to middleware ' . static::class . '::handle()');
    }
}
