<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use YADSP\Matcher\MatcherFactoryInterface;
use YADSP\Matcher\MatcherInterface;

class MatcherFactory implements MatcherFactoryInterface
{
    use LoggerAwareTrait;

    public function __construct(LoggerInterface|null $logger = null)
    {
        $this->logger = $logger;
    }

    public function supports(string $type): bool
    {
        return in_array($type, ['client_address', 'client_port', 'http_method', 'url', 'user_agent']);
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
            case 'client_address':
                $matcher = new ClientAddressMatcher($values);
                break;
            case 'client_port':
                $matcher = new ClientPortMatcher($values);
                break;
            case 'http_method':
                $matcher = new MethodMatcher($values);
                break;
            case 'url':
                $matcher = new UrlMatcher($values);
                break;
            case 'user_agent':
                return new UserAgentMatcher($values);
            default:
                throw new \Exception("Invalid request matching configuration: '$type' => " . var_export($values, true));
        }
        if ($this->logger && $matcher instanceof LoggerAwareInterface) {
            $matcher->setLogger($this->logger);
        }
        return $matcher;
    }
}
