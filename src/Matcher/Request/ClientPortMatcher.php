<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Http\Message\ServerRequestInterface;
use YADSP\Matcher\RegExpListMatcherTrait;

/// @todo make it easier to explicitly match only client ip, port, hostname
class ClientPortMatcher extends BaseMatcher
{
    use RegExpListMatcherTrait;

    /**
     * @param string|array $filter
     * @throws \Exception
     */
    public function __construct(string|array $filter)
    {
        $this->setAllowedValues($filter);
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        $env = $request->getServerParams();
        /// @todo... log a warning if we are not passed this env var
        $clientPort = $env['REMOTE_PORT'] ?? '';

        return $this->matchesString($clientPort);
    }

    protected function normalizeValue(string $value): string
    {
        return $this->wildcardToRegexp($value);
    }
}
