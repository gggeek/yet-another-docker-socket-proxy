<?php

namespace YADSP\Matcher\Response;

use Psr\Http\Message\ResponseInterface;

class BodyMatcher extends BaseMatcher
{
    public function matchesResponse(ResponseInterface $response): bool
    {
/// @todo...
        return false;
    }
}
