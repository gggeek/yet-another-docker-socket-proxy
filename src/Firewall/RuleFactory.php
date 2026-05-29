<?php

namespace YADSP\Firewall;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use YADSP\Logger\PrivateLoggerTrait;
use YADSP\Matcher\ChainFactory;
use YADSP\Matcher\Logic\AndMatcher;
use YADSP\Matcher\Logic\MatcherFactory as LogicMatcherFactory;
use YADSP\Matcher\MatcherFactoryInterface;
use YADSP\Matcher\MatcherInterface;
use YADSP\Matcher\Request\MatcherFactory as RequestMatcherFactory;

class RuleFactory
{
    use LoggerAwareTrait;
    use PrivateLoggerTrait;

    protected MatcherFactoryInterface|null $matcherFactory = null;

    public function __construct(LoggerInterface|null $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $config
     * @return Rule
     * @throws \Exception
     */
    public function fromConfiguration(array $config): Rule
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
                'req_action' => Rule::ACTION_ALLOW,
                'req_filters' => [],
                'resp_match' => ['always' => true],
                'resp_action' => Rule::ACTION_ALLOW,
                'resp_filters' => []
            ];

        $matcherFactory = $this->getMatcherFactory([]);

        return new Rule(
            $this->parseMatcherConfiguration($config['req_match'], $matcherFactory),
            $this->parseFiltersConfiguration($config['req_filters']),
            $config['req_action'],
            $this->parseMatcherConfiguration($config['resp_match'], $matcherFactory),
            $this->parseFiltersConfiguration($config['resp_filters']),
            $config['resp_action']
        );
    }

    /**
     * @throws \Exception
     */
    protected function parseMatcherConfiguration(array $matcherSpec, MatcherFactoryInterface $matcherFactory): MatcherInterface
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

    protected function parseFiltersConfiguration(array $ruleSpec): array
    {
/// @todo...
        return [];
    }

    /**
    /// @todo...split reqM and respM
     * @param array $config
     * @return \YADSP\Matcher\MatcherFactoryInterface
     * @throws \Exception
     */
    protected function getMatcherFactory(array $config): MatcherFactoryInterface
    {
        if ($this->matcherFactory === null) {
            $logicMatcherFactory = new LogicMatcherFactory();
            $this->matcherFactory = new ChainFactory([new RequestMatcherFactory(), $logicMatcherFactory]);
            // inception! ;-)
            $logicMatcherFactory->setMatcherFactory($this->matcherFactory);
        }
        return $this->matcherFactory;
    }

}
