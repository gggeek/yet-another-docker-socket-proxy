<?php
declare(strict_types=1);

namespace YADSP\Matcher\Response;

use YADSP\Matcher\MatcherFactoryInterface;
use YADSP\Matcher\MatcherInterface;

class MatcherFactory implements MatcherFactoryInterface
{
    public function supports(string $type): bool
    {
        return in_array($type, ['body']);
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
            case 'body':
                return new BodyMatcher($values);
        }
        throw new \Exception("Invalid response matching configuration: '$type' => " . var_export($values, true));
    }
}
