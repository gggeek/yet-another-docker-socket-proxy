<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Http\Message\ServerRequestInterface;
use YADSP\Matcher\RegExpListMatcherTrait;

class ClientMatcher extends BaseMatcher
{
    use RegExpListMatcherTrait;

    public function __construct(string $filter)
    {
/// @todo...
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return $this->matchRegexp($request->getServerParams(...));
    }
}
