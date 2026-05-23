<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Http\Message\ServerRequestInterface;

interface RequestMatcherInterface
{
    function matchesRequest(ServerRequestInterface $request): bool;
}
