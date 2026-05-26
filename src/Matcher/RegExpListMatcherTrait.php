<?php
declare(strict_types=1);

namespace YADSP\Matcher;

/**
 * Allows matching a string that must fall within an allowed list of regexes
 */
trait RegExpListMatcherTrait
{
    /** @var string[] $allowedValues */
    protected array $allowedValues;
    protected string $regexpDelimiter = '#';
    protected string $regexp;

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
            $this->allowedValues = [];
            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new \Exception('Only arrays of strings are allowed as argument to the matcher');
                }
                $this->allowedValues[] = $this->normalizeValue($value);
            }
        } else {
            $this->allowedValues = [$this->normalizeValue($values)];
        }
        $this->regexp = $this->regexpDelimiter . '(' . implode('|', $this->allowedValues) . ')' . $this->regexpDelimiter;
    }

    protected function matchesString(string $value): bool
    {
        return (bool)preg_match($this->regexp, $value);
    }

    /**
     * To be reimplemented in subclasses
     * @param string $value
     * @return string
     */
    protected function normalizeValue(string $value): string
    {
        return preg_quote($value, $this->regexpDelimiter);
    }

    protected function wildcardToRegexp(string $value): string
    {
        return '^' . str_replace(['*'], ['.*'], preg_quote($value, $this->regexpDelimiter)) . '$';
    }
}
