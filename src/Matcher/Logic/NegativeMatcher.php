<?php
declare(strict_types=1);

namespace YADSP\Matcher\Logic;

use YADSP\Matcher\Request\RequestMatcherInterface;
use YADSP\Matcher\Response\ResponseMatcherInterface;

class NegativeMatcher implements RequestMatcherInterface, ResponseMatcherInterface
{
    protected MatcherInterface $matcher;

    public function __construct(MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    public function matches(...$items): bool
    {
        return !$this->matcher->matches(...$items);
    }
}
