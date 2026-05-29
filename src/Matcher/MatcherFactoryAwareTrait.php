<?php
declare(strict_types=1);


namespace YADSP\Matcher;

trait MatcherFactoryAwareTrait
{
    protected MatcherFactoryInterface $matcherFactory;

    public function setMatcherFactory(MatcherFactoryInterface $matcherFactory): void
    {
        $this->matcherFactory = $matcherFactory;
    }
}
