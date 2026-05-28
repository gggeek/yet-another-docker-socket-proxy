<?php
declare(strict_types=1);

namespace YADSP\Matcher\Response;

use Psr\Http\Message\ResponseInterface;
use YADSP\Matcher\MatcherInterface;

/// @todo check: can we avoid making this extend MatcherInterface?
interface ResponseMatcherInterface extends MatcherInterface
{
    function matchesResponse(ResponseInterface $response): bool;
}
