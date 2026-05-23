<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use YADSP\MatcherInterface;

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
        //if (is_array($config) && count($config) == 1) {
            $target = strtolower(trim($type));
            switch($target) {
                case 'client':
                    return new ClientMatcher($values);
                case 'method':
                    return new MethodMatcher($values);
                case 'url':
                    return new UrlMatcher($values);
            }
        //}
        throw new \Exception("Invalid request matching configuration: $type => " . var_export($config, true));
    }
}
