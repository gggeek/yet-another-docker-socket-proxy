<?php
declare(strict_types=1);

namespace YADSP\Matcher\Logic;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use YADSP\Matcher\Request\RequestMatcherInterface;
use YADSP\Matcher\Response\ResponseMatcherInterface;

class AlwaysMatcher implements RequestMatcherInterface, ResponseMatcherInterface
{
    public function matches(...$items): bool
    {
        /// @todo log a warning if passed in any items
        return true;
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return true;
    }

    public function matchesResponse(ResponseInterface $response): bool
    {
        return true;
    }
}
