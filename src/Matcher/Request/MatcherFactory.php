<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use YADSP\MatcherInterface;

/// @todo make this logger-aware, and pass the logger on to the created matchers if they also are
class MatcherFactory
{
    /**
     * @param string $type
     * @param mixed $values
     * @return MatcherInterface
     * @throws \Exception
     */
    public function fromConfiguration(string $type, mixed $values): MatcherInterface
    {
        $target = strtolower(trim($type));
        switch($target) {
            case 'client_address':
                return new ClientAddressMatcher($values);
            case 'client_port':
                return new ClientPortMatcher($values);
            case 'method':
                return new MethodMatcher($values);
            case 'url':
                return new UrlMatcher($values);
            case 'user_agent':
                return new UserAgentMatcher($values);
        }
        throw new \Exception("Invalid request matching configuration: '$type' => " . var_export($values, true));
    }
}
