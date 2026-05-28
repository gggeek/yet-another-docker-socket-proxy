<?php
declare(strict_types=1);

namespace YADSP\Firewall;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use YADSP\Filter\Bidirectional\BidirectionalFilterInterface;
use YADSP\Logger\PrivateLoggerTrait;
use YADSP\Stdlib;

/**
 * The class doing the actual filtering of Requests and Responses
 */
class Firewall implements BidirectionalFilterInterface, LoggerAwareInterface
{
    protected const DefaultFallbackConfiguration = [
        'req_match' => [
            'url' => '/_ping', // /version gets disabled out of the box - in case the version number might be useful to attackers...
            'http_method' => ['GET', 'HEAD'],
        ],
        'req_filters' => [],
        'req_action' => 'allow',
        'resp_match' => ['always' => true],
        'resp_action' => 'allow',
        'resp_filters' => [],
    ];

    protected static array|null $fallbackConfiguration = null;

    use LoggerAwareTrait;
    use PrivateLoggerTrait;

    /** @var Rule[] */
    protected array $rules;
    protected null|Rule $currentRule = null;

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
     * @param string $configuration
     * @param LoggerInterface|null $logger
     * @return static
     * @throws \Exception
     */
    public static function fromConfigString(string $configuration, LoggerInterface|null $logger): static
    {
        //$logger?->debug("Loading firewall configuration from string");
        if (trim($configuration) === '') {
            $config = [];
        } else {
            $config = @json_decode($configuration, true);
            if (!is_array($config)) {
                throw new \Exception("The configuration passed in is not a valid json array. Error: " . json_last_error_msg());
            }
        }
        return static::fromConfiguration($config, $logger);
    }

    /**
     * @param array $config
     * @param LoggerInterface|null $logger
     * @return static
     * @throws \Exception
     */
    public static function fromConfiguration(array $config, LoggerInterface|null $logger): static
    {
        if (!$config) {
            $logger?->warning("Empty configuration passed in. The firewall will only let trough 'ping' API calls");
        }

        foreach($config as $ruleName => $ruleConfig) {
            if (!is_array($ruleConfig)) {
                throw new \Exception("Bad configuration: the value for firewall rule '$ruleName' should be an array");
            }
        }

        if (array_key_exists('*', $config)) {
            // add the fallback rules
            $fallbackConfig = $config['*'] + static::getFallbackConfiguration();
            // make sure that this is the last rule
            unset($config['*']);
            $config['*'] = $fallbackConfig;

        } else {
            $config['*'] = static::getFallbackConfiguration();
        }

        $rules = [];
        foreach($config as $ruleName => $ruleSpec) {
            try {
                $rule = Rule::fromConfiguration($ruleSpec);
                if ($logger && $rule instanceof LoggerAwareInterface) {
                    $rule->setLogger($logger);
                }
                $rules[$ruleName] = $rule;
            } catch (\Exception $e) {
                throw new \Exception("Error parsing firewall rule '$ruleName': " . $e->getMessage());
            }
        }

        return new static($rules, $logger);
    }

    /**
     * @param Rule[] $rules
     * @throws \Exception
     */
    public function __construct(array $rules, LoggerInterface|null $logger = null)
    {
        $this->logger = $logger;
        if (!Stdlib::array_of($rules, Rule::class)) {
            throw new \Exception("Array passed to " . static::class . " constructor must contain only instances of " . Rule::class);
        }
        $this->rules = $rules;
    }

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface|false
    {
        $this->currentRule = null;
        foreach($this->rules as $ruleName => $rule) {
            if ($rule->matchesRequest($request)) {
                $this->debug("Firewall rule '$ruleName' matched request: " . $this->request2Log($request));
                $this->currentRule = $rule;
                return $rule->filterRequest($request);
            }
        }
        $this->debug("No firewall rule matched request: " . $this->request2Log($request));
        return false;
    }

    public function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface|false
    {
        $response = $this->currentRule->filterResponse($response, $request);
        $this->currentRule = null;
        return $response;
    }

    /**
     * Returns the default filter applied to all clients - let ping and version requests through
     * @return array
     * @todo allow access to /events?
     */
    protected static function getFallbackConfiguration(): array
    {
        return is_array(static::$fallbackConfiguration) ? static::$fallbackConfiguration : static::DefaultFallbackConfiguration;
    }

    public static function setFallbackConfiguration(array $config): void
    {
        /// @todo validate the config now instead of relying on static::fromConfiguration
        static::$fallbackConfiguration = $config;
    }

    // *** Logging ***

    protected function request2Log(ServerRequestInterface $request): string
    {
        return $request->getMethod() . ' ' . $request->getUri();
    }
}
