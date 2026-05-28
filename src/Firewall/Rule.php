<?php
declare(strict_types=1);

namespace YADSP\Firewall;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareTrait;
use YADSP\Filter\Request\RequestFilterInterface;
use YADSP\Filter\Response\ResponseFilterInterface;
use YADSP\Matcher\ChainFactory;
use YADSP\Matcher\Logic\AndMatcher;
use YADSP\Matcher\Logic\MatcherFactory as LogicMatcherFactory;
use YADSP\Matcher\MatcherFactoryInterface;
use YADSP\Matcher\MatcherInterface;
use YADSP\Matcher\Request\MatcherFactory as RequestMatcherFactory;
use YADSP\Matcher\Request\RequestMatcherInterface;
use YADSP\Matcher\Response\ResponseMatcherInterface;

class Rule implements RequestMatcherInterface, RequestFilterInterface, ResponseFilterInterface
{
    const ACTION_ALLOW = 'allow';
    const ACTION_DENY = 'deny';
    /// @todo (feature creep...)
    //const ACTION_RERUN = 'rerun';

    protected static MatcherFactoryInterface|null $matcherFactory = null;

    use LoggerAwareTrait;

    protected RequestMatcherInterface $requestMatcher;
    /** @var RequestFilterInterface[] */
    protected array $requestFilters = [];
    protected string $requestAction = self::ACTION_ALLOW;
    protected null|ResponseMatcherInterface $responseMatcher;
    /** @var ResponseFilterInterface[] */
    protected array $responseFilters = [];
    protected string $responseAction = self::ACTION_ALLOW;

    /**
     * @param array $config
     * @return static
     * @throws \Exception
     */
    public static function fromConfiguration(array $config): static
    {
        if (!$config) {
            throw new \Exception("Bad configuration: the value for firewall rule should not be an empty array");
        }

        if (!array_key_exists('req_match', $config) && !array_key_exists('req_action', $config) && !array_key_exists('req_filters', $config)
            && !array_key_exists('resp_match', $config) && !array_key_exists('resp_action', $config) && !array_key_exists('resp_filters', $config)) {
            $config = ['req_match' => $config];
        }

        if ($badKeys = array_diff(array_keys($config), ['req_match', 'req_action', 'req_filters', 'resp_match', 'resp_action', 'resp_filters'])) {
            throw new \Exception("Bad configuration: the value for firewall rule should not have keys: " . implode(',', $badKeys));
        }

/// @todo... validate types? (either here or in the constructor)

/// @todo... only allow configs with no req_match to be accepted if
///          1. req_action is not deny and
///          2. either there are req_filters or resp_filters or resp_match+resp_action=deny

/// @todo... throw if there are req_filters, resp_match, resp_action or resp_filters when req_action is deny
/// @todo... throw if there are resp_filters when resp_action is deny

        $config = $config + [
            'req_match' => ['always' => true],
            'req_action' => self::ACTION_ALLOW,
            'req_filters' => [],
            'resp_match' => ['always' => true],
            'resp_action' => self::ACTION_ALLOW,
            'resp_filters' => []
        ];

        $matcherFactory = static::getMatcherFactory([]);

        return new static(
            static::parseMatcherConfiguration($config['req_match'], $matcherFactory),
            static::parseFiltersConfiguration($config['req_filters']),
            $config['req_action'],
            static::parseMatcherConfiguration($config['resp_match'], $matcherFactory),
            static::parseFiltersConfiguration($config['resp_filters']),
            $config['resp_action']
        );
    }

    /**
     * @throws \Exception
     */
    protected static function parseMatcherConfiguration(array $matcherSpec, MatcherFactoryInterface $matcherFactory): MatcherInterface
    {
        if (!$matcherSpec) {
            throw new \Exception("The value for each rule 'match' section must be a non-empty array of conditions");
        }

        if (count($matcherSpec) === 1) {
            $matcher = $matcherFactory->fromConfiguration(array_key_first($matcherSpec), reset($matcherSpec));
        } else {
            $matcher = new AndMatcher([]);
            foreach ($matcherSpec as $type => $values) {
                $matcher->addMatcher($matcherFactory->fromConfiguration((string)$type, $values));
            }
        }
        return $matcher;
    }

    protected static function parseFiltersConfiguration(array $ruleSpec): array
    {
/// @todo...
        return [];
    }

    /**
     * @param array $config
     * @return \YADSP\Matcher\MatcherFactoryInterface
     * @throws \Exception
     */
    protected static function getMatcherFactory(array $config): MatcherFactoryInterface
    {
        if (static::$matcherFactory === null) {
            static::$matcherFactory = new ChainFactory([new RequestMatcherFactory(), new LogicMatcherFactory()]);
        }
        return static::$matcherFactory;
    }

    /**
     * @param RequestMatcherInterface[] $requestMatch
     * @param RequestFilterInterface[] $requestFilters
     * @param string $requestAction
     * @param ResponseMatcherInterface[] $responseMatch
     * @param ResponseFilterInterface[] $responseFilters
     * @param string $responseAction
     */
    public function __construct(RequestMatcherInterface $requestMatcher, array $requestFilters = [], string $requestAction = self::ACTION_ALLOW,
        ResponseMatcherInterface|null $responseMatcher = null, array $responseFilters = [], string $responseAction = self::ACTION_ALLOW)
    {
/// @todo... add type-checking of filter arrays and validation of actions strings
        $this->requestMatcher = $requestMatcher;
        $this->requestFilters = $requestFilters;
        $this->requestAction = $requestAction;
        $this->responseMatcher = $responseMatcher;
        $this->responseFilters = $responseFilters;
        $this->responseAction = $responseAction;
    }

    public function matches(...$items): bool
    {
        if (count($items) !== 1 || ! $items[0] instanceof ServerRequestInterface) {
            throw new \Exception('Rule expected a ServerRequestInterface to match but got instead a ' . gettype($items[0]));
        }

        return $this->matchesRequest($items[0]);
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return $this->requestMatcher->matchesRequest($request);
    }

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface|false
    {
        if ($this->requestAction === self::ACTION_DENY) {
            return false;
        }

        foreach ($this->requestFilters as $requestFilter) {
            $request = $requestFilter->filterRequest($request);
            if ($request === false || $request instanceof ResponseInterface) {
                return $request;
            }
        }
        return $request;
    }

    protected function matchesResponse(ResponseInterface $response): bool
    {
        return $this->responseMatcher->matchesResponse($response);
    }

    public function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface|false
    {
        if ($this->matchesResponse($response)) {
            if ($this->responseAction === self::ACTION_DENY) {
                return false;
            }
            foreach ($this->responseFilters as $responseFilter) {
                $response = $responseFilter->filterResponse($response, $request);
                if ($response === false) {
                    return false;
                }
            }
        }
        return $response;
    }
}
