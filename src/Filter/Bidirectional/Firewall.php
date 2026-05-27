<?php
declare(strict_types=1);

namespace YADSP\Filter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use YADSP\FilterInterface;
use YADSP\Logger\PrivateLoggerTrait;
use YADSP\Matcher\ChainFactory;
use YADSP\Matcher\Logic\AndMatcher;
use YADSP\Matcher\Logic\OrMatcher;
use YADSP\Matcher\Logic\MatcherFactory as LogicMatcherFactory;
use YADSP\Matcher\Request\MatcherFactory as RequestMatcherFactory;
use YADSP\MatcherFactoryInterface;
use YADSP\MatcherInterface;

/**
 * The class doing the actual filtering of Requests and Responses
 */
class Firewall implements FilterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PrivateLoggerTrait;

    protected static $defaultFallbackConfiguration = [
        [
            'url' => '/_ping',
            'http_method' => ['GET', 'HEAD'],
        ],
        /// @todo should we disable this? The version number might be useful to attackers...
        [
            'url' => '/version',
            'http_method' => ['GET'],
        ]
    ];

    protected MatcherFactoryInterface $matcherFactory;
    /** @var MatcherInterface[] */
    protected array $requestMatchers;
    protected array|null $fallbackConfiguration = null;

    /**
     * @param string $configuration
     * @param LoggerInterface|null $logger
     * @return static
     * @throws \Exception
     */
    public static function fromConfigString(string $configuration, LoggerInterface|null $logger): static
    {
        if (trim($configuration) === '') {
            $config = [];
        } else {
            $config = @json_decode($configuration, true);
            if (!is_array($config)) {
                throw new \Exception("The configuration passed in is not a valid json array. Error: " . json_last_error_msg());
            }
        }
        return new static($config, $logger);
    }

    /**
     * @param string $configurationFile
     * @param LoggerInterface|null $logger
     * @return static
     * @throws \Exception
     * @todo allow parsing yaml files besides json ones
     */
    public static function fromConfigFile(string $configurationFile, LoggerInterface|null $logger): static
    {
        $logger?->debug("Loading firewall configuration from file '$configurationFile'");
        if (($configString = @file_get_contents($configurationFile)) === false) {
            throw new \Exception("Can not load configuration file '$configurationFile' " . error_get_last()['message']);
        }
        return static::fromConfigString($configString, $logger);
    }

    /**
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config, LoggerInterface|null $logger = null)
    {
        $this->logger = $logger;
        if (!$config) {
            $this->warning("Empty configuration passed in. The proxy will only let trough 'ping' and 'version' API calls");
        }
        $this->requestMatchers = $this->parseConfiguration($config);
    }

    /**
     * @param array $config
     * @return MatcherInterface[]
     * @throws \Exception
     */
    protected function parseConfiguration(array $config): array
    {
        $this->matcherFactory = $this->setupMatcherFactory($config);

        foreach($config as $ruleName => $ruleSpec) {
            if (!is_array($ruleSpec)) {
                throw new \Exception("Bad configuration: the value for firewall rule '$ruleName' should be an array");
            }
        }

        if (array_key_exists('*', $config)) {
            // add the fallback rules
            $fallbackConfig = $config['*'] + $this->fallbackConfiguration();
            // make sure that this is the last rule
            unset($config['*']);
            $config['*'] = $fallbackConfig;

        } else {
            $config['*'] = $this->fallbackConfiguration();
        }

        $matchers = [];
        foreach($config as $ruleName => $ruleSpec) {
            try {
                $matchers[$ruleName] = $this->parseRuleSpecConfiguration($ruleSpec);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing firewall rule '$ruleName': " . $e->getMessage());
            }
        }
        return $matchers;
    }

    /**
     * @param array $config
     * @return MatcherFactoryInterface
     * @throws \Exception
     */
    protected function setupMatcherFactory(array $config): MatcherFactoryInterface
    {
        return new ChainFactory([new RequestMatcherFactory(), new LogicMatcherFactory()]);
    }

    /**
     * @param array $ruleSpec
     * @return MatcherInterface
     * @throws \Exception
     */
    protected function parseRuleSpecConfiguration(array $ruleSpec): MatcherInterface
    {
        if (count($ruleSpec) === 1) {
            $ruleCase = reset($ruleSpec);
            if (!is_array($ruleCase) || !$ruleCase) {
                throw new \Exception('The value for each rule must be a non-empty array of cases');
            }
            if (count($ruleCase) === 1) {
                $matcher = $this->matcherFactory->fromConfiguration(array_key_first($ruleCase), reset($ruleCase));
            } else {
                $matcher = new AndMatcher([]);
                foreach ($ruleCase as $type => $value) {
                    $matcher->addMatcher($this->matcherFactory->fromConfiguration($type, $value));
                }
            }
        } else {
            $matcher = new OrMatcher([]);
            /// @todo should we check that there is at least 1 element?
            foreach ($ruleSpec as $ruleCase) {
                if (!is_array($ruleCase) || !$ruleCase) {
                    throw new \Exception('The value for each rule must be a non-empty array of cases');
                }
                if (count($ruleCase) === 1) {
                    $matcher = $this->matcherFactory->fromConfiguration(array_key_first($ruleCase), reset($ruleCase));
                } else {
                    $matcher = new AndMatcher([]);
                    foreach ($ruleCase as $type => $value) {
                        $matcher->addMatcher($this->matcherFactory->fromConfiguration($type, $value));
                    }
                }
            }
        }
        return $matcher;
    }

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface|false
    {
        foreach($this->requestMatchers as $ruleName => $matcher) {
            if ($matcher->matches($request)) {
                $this->debug("Firewall rule '$ruleName' matched request: " . $this->request2Log($request));
                return $request;
            }
        }
        $this->debug("No firewall rule matched request: " . $this->request2Log($request));
        return false;
    }

    public function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
/// @todo...
        return $response;
    }

    /**
     * Returns the default filter applied to all clients - let ping and version requests through
     * @return array
     * @todo allow access to /events?
     */
    protected function fallbackConfiguration(): array
    {
        return is_array($this->fallbackConfiguration) ? $this->fallbackConfiguration : static::$defaultFallbackConfiguration;
    }

    public function setFallbackConfiguration(array $config): void
    {
        /// @todo validate the config
        $this->fallbackConfiguration = $config;
    }

    // *** Logging ***

    protected function request2Log(ServerRequestInterface $request): string
    {
        return $request->getMethod() . ' ' . $request->getUri();
    }
}
