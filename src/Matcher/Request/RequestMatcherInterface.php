<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Http\Message\ServerRequestInterface;
use YADSP\Matcher\MatcherInterface;

/// @todo check: can we avoid making this extend MatcherInterface?
interface RequestMatcherInterface extends MatcherInterface
{
    function matchesRequest(ServerRequestInterface $request): bool;
}
