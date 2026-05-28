<?php
declare(strict_types=1);

namespace YADSP\Matcher\Logic;

use YADSP\Matcher\MatcherFactoryInterface;
use YADSP\Matcher\MatcherInterface;

class MatcherFactory implements MatcherFactoryInterface
{
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
/// @todo... we need to transform $values into $matchers
                return new AndMatcher($values);
            case 'never':
                /// @todo log a warning if $values is falsey
                return new NeverMatcher();
            case 'not':
/// @todo... we need to transform $values into $matchers
                return new NegativeMatcher($values);
            case 'or':
/// @todo... we need to transform $values into $matchers
                return new OrMatcher($values);
        }
        throw new \Exception("Invalid logical matching configuration: '$type' => " . var_export($values, true));
    }
}
