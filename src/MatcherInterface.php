<?php

namespace YADSP;

use Psr\Http\Message\ServerRequestInterface;

interface MatcherInterface
{
    public function matches(...$items): bool;
}
