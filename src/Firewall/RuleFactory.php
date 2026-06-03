<?php

namespace YADSP\Firewall;

use YADSP\Matcher\Request\MatcherFactory as RequestMatcherFactory;
use YADSP\Matcher\Response\MatcherFactory as ResponseMatcherFactory;
use YAWAF\Core\Firewall\RuleFactory as BaseRuleFactory;
use YAWAF\Core\Matcher\ChainFactory;
use YAWAF\Core\Matcher\Logic\MatcherFactory as LogicMatcherFactory;
use YAWAF\Core\Matcher\MatcherFactoryInterface;

class RuleFactory extends BaseRuleFactory
{
    /**
     * Same as parent, but return our own type of Factory.
     * @param array $config
     * @return MatcherFactoryInterface
     * @throws \Exception
     */
    protected function getRequestMatcherFactory(array $config): MatcherFactoryInterface
    {
        if ($this->requestMatcherFactory === null) {
            $logicMatcherFactory = new LogicMatcherFactory($this->logger);
            $this->requestMatcherFactory = new ChainFactory([new RequestMatcherFactory($this->logger), $logicMatcherFactory]);
            // inception! ;-)
            $logicMatcherFactory->setMatcherFactory($this->requestMatcherFactory);
        }
        return $this->requestMatcherFactory;
    }

    /**
     * Same as parent, but return our own type of Factory.
     * @param array $config
     * @return MatcherFactoryInterface
     * @throws \Exception
     */
    protected function getResponseMatcherFactory(array $config): MatcherFactoryInterface
    {
        if ($this->responseMatcherFactory === null) {
            $logicMatcherFactory = new LogicMatcherFactory($this->logger);
            $this->responseMatcherFactory = new ChainFactory([new ResponseMatcherFactory($this->logger), $logicMatcherFactory]);
            // inception! ;-)
            $logicMatcherFactory->setMatcherFactory($this->responseMatcherFactory);
        }
        return $this->responseMatcherFactory;
    }
}
