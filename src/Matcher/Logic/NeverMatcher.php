<?php
declare(strict_types=1);

namespace YADSP\Matcher\Logic;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use YADSP\Matcher\Request\RequestMatcherInterface;
use YADSP\Matcher\Response\ResponseMatcherInterface;

class NeverMatcher implements RequestMatcherInterface, ResponseMatcherInterface
{

    public function matches(...$items): bool
    {
        /// @todo log a warning if passed in any items
        return false;
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return false;
    }

    public function matchesResponse(ResponseInterface $response): bool
    {
        return false;
    }
}
