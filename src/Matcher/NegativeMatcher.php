<?php
declare(strict_types=1);

namespace YADSP\Matcher;

use Psr\Http\Message\ServerRequestInterface;
use YADSP\MatcherInterface;

class NegativeMatcher implements MatcherInterface
{
    protected MatcherInterface $matcher;

    public function __construct(MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    public function matches(ServerRequestInterface $request): bool
    {
        return !$this->matcher->matches($request);
    }
}
