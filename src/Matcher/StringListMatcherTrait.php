<?php

namespace YADSP\Matcher;

/**
 * Allows matching a string that must fall within an allowed list of values
 */
trait StringListMatcherTrait
{
    /** @var string[] $allowedValues */
    protected array $allowedValues;

    /**
     * @param string|string[] $values
     * @throws \Exception
     */
    protected function setAllowedValues(string|array $values): void
    {
        if (is_array($values)) {
            if (!$values) {
                throw new \Exception('At least one string is required as argument to the matcher');
            }
            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new \Exception('Only arrays of strings are allowed as argument to the matcher');
                }
                $this->allowedValues[$this->normalizeValue($value)] = true;
            }
        } else {
            $this->allowedValues = [$this->normalizeValue($values) => true];
        }
    }

    /**
     * To be reimplemented in subclasses
     * @param string $value
     * @return string
     */
    protected function normalizeValue(string $value): string
    {
        return $value;
    }

    protected function matchesString(string $value): bool
    {
        return array_key_exists($value, $this->allowedValues);
    }
}
