<?php
declare(strict_types=1);

namespace YADSP\Matcher\Logic;

use YADSP\Matcher\MatcherFactoryAwareTrait;
use YADSP\Matcher\MatcherFactoryInterface;
use YADSP\Matcher\MatcherInterface;

class MatcherFactory implements MatcherFactoryInterface
{
    use MatcherFactoryAwareTrait;

    public function supports(string $type): bool
    {
        return in_array($type, ['always', 'and', 'never', 'not', 'or']);
    }

    /**
     * @param string $type
     * @param mixed $values
     * @return \YADSP\Matcher\MatcherInterface
     * @throws \Exception
     */
    public function fromConfiguration(string $type, mixed $values): MatcherInterface
    {
        $target = strtolower(trim($type));
        switch($target) {
            case 'always':
                /// @todo log a warning if $values is falsey
                return new AlwaysMatcher();
            case 'and':
            case 'or':
                if (!is_array($values) || count($values) <= 1) {
                    throw new \Exception("Invalid logical matching configuration: '$type' should have as value an array with at least 2 elements");
                }
                $matchers = [];
                foreach ($values as $type => $subValues) {
                    $matchers[] = $this->matcherFactory->fromConfiguration($type, $subValues);
                }
                return $target === 'and' ? new AndMatcher($matchers) : new OrMatcher($matchers);
            case 'never':
                /// @todo log a warning if $values is falsey
                return new NeverMatcher();
            case 'not':
                if (!is_array($values) || count($values) !== 1) {
                    throw new \Exception("Invalid logical matching configuration: '$type' should have as value an array with a single element");
                }
                return new NegativeMatcher($this->matcherFactory->fromConfiguration(array_key_first($values), reset($values)));
        }
        throw new \Exception("Invalid logical matching configuration: '$type' => " . var_export($values, true));
    }
}
