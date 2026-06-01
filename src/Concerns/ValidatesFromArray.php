<?php

namespace Blume\LaravelDTO\Concerns;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionProperty;
use Blume\LaravelDTO\Attributes\Cast;
use Blume\LaravelDTO\Attributes\DefaultValue;
use Blume\LaravelDTO\Attributes\Map;
use Blume\LaravelDTO\Attributes\Validation\Rule as ValidationRule;
use Blume\LaravelDTO\BaseDTO;

/**
 * Laravel validation + attribute rules/defaults + property casting for any {@see BaseDTO}.
 *
 * Use on DTOs that receive untrusted array payloads (HTTP requests, external APIs, webhooks, etc.).
 */
trait ValidatesFromArray
{
    /**
     * Validate $data, merge defaults, then assign to public properties (with {@see Cast} applied).
     *
     * @throws ValidationException
     */
    protected function validateHydrateAndMap(array $data): void
    {
        $validator = $this->validate($data);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        foreach ($this->mergeDefaults() as $key => $value) {
            if (! Arr::has($validated, $key)) {
                Arr::set($validated, $key, $value);
            }
        }

        $this->mapData($validated);
    }

    /**
     * Build a validator without running it — useful to inspect rules or fail softly.
     */
    protected function validate(array $data): ValidatorContract
    {
        return Validator::make(
            $data,
            $this->mergeRules(),
            $this->messages()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Merge defaults from {@see defaults()} and {@see DefaultValue} attributes.
     *
     * @return array<string, mixed>
     */
    protected function mergeDefaults(): array
    {
        return array_merge($this->extractDefaultsFromAttributes(), $this->defaults());
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractDefaultsFromAttributes(): array
    {
        $defaults = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $key = $property->getName();
            $defaultValue = null;
            $hasDefault = false;

            foreach ($property->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();

                if ($instance instanceof Map) {
                    $key = $instance->key;
                } elseif ($instance instanceof DefaultValue) {
                    $defaultValue = $instance->value;
                    $hasDefault = true;
                }
            }

            if ($hasDefault) {
                $defaults[$key] = $defaultValue;
            }
        }

        return $defaults;
    }

    /**
     * Merge rules from {@see rules()} and validation attributes on public properties.
     *
     * @return array<string, mixed>
     */
    protected function mergeRules(): array
    {
        $methodRules = $this->rules();
        $attributeRules = $this->extractRulesFromAttributes();

        foreach ($attributeRules as $property => $rules) {
            if (! isset($methodRules[$property])) {
                $methodRules[$property] = $rules;
            } elseif (is_array($methodRules[$property])) {
                $methodRules[$property] = array_merge($rules, $methodRules[$property]);
            }
        }

        return $methodRules;
    }

    /**
     * @return array<string, list<string|array>>
     */
    protected function extractRulesFromAttributes(): array
    {
        $rules = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $key = $property->getName();
            $propertyRules = [];

            foreach ($property->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();

                if ($instance instanceof Map) {
                    $key = $instance->key;
                } elseif ($instance instanceof ValidationRule) {
                    $propertyRules[] = $instance->toValidationRule();
                }
            }

            if ($propertyRules !== []) {
                $rules[$key] = $propertyRules;
            }
        }

        return $rules;
    }

    /**
     * Assign validated values to public properties, applying {@see Cast} attributes.
     */
    protected function mapData(array $data): void
    {
        foreach ($this->getProperties() as $name => $attributes) {
            $sourceKey = $name;
            $castAttributes = [];

            foreach ($attributes as $attribute) {
                if ($attribute instanceof Map) {
                    $sourceKey = $attribute->key;
                } elseif ($attribute instanceof Cast) {
                    $castAttributes[] = $attribute;
                }
            }

            if (! Arr::has($data, $sourceKey)) {
                continue;
            }

            $value = Arr::get($data, $sourceKey);

            foreach ($castAttributes as $cast) {
                $value = $cast->cast($name, $value);
            }

            $this->{$name} = $value;
            $this->setKeys[] = $name;
        }
    }

    /**
     * Only serializes properties the request actually set (PATCH semantics).
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->setKeys as $name) {
            $data[$name] = $this->convertValueToArray($this->{$name});
        }

        return $data;
    }
}
