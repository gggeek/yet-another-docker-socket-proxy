<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Log\LoggerAwareInterface;
use YAWAF\Core\Matcher\MatcherInterface;
use YAWAF\Core\Matcher\Request\MatcherFactory as BaseMatcherFactory;

class MatcherFactory extends BaseMatcherFactory
{
    // No extra tags compared to parent, for the moment...
    /*public function supports(string $type): bool
    {
        $type = strtolower(preg_replace('/:[0-9]+$/', '', trim($type)));
        return in_array($type, ['body', 'client_address', 'client_port', 'http_header', 'http_method', 'url', 'user_agent']);
    }*/

    /**
     * @param string $type
     * @param mixed $values
     * @return MatcherInterface
     * @throws \Exception
     */
    public function fromConfiguration(string $type, mixed $values): MatcherInterface
    {
        $target = strtolower(preg_replace('/:[0-9]+$/', '', trim($type)));
        switch($target) {
            case 'url_path':
                // use a custom URL matcher
                $matcher = new PathMatcher($values);
                break;
            default:
                return parent::fromConfiguration($type, $values);
        }
        if ($this->logger && $matcher instanceof LoggerAwareInterface) {
            $matcher->setLogger($this->logger);
        }
        return $matcher;
    }
}
