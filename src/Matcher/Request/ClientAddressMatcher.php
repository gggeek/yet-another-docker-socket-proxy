<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Http\Message\ServerRequestInterface;
use YADSP\Matcher\RegExpListMatcherTrait;

/// @todo make it easier to explicitly match only client ip, port, hostname
class ClientMatcher extends BaseMatcher
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
        /// @todo... log a warning if we are not passed any of these env vars
        $clientFullAddress = $env['REMOTE_ADDR'] . ':' . $env['REMOTE_PORT'];
        /// @todo should we reverse-resolve the hostname if we are only passed an IP?
        $clientFullHostname = $env['REMOTE_HOST'] . ':' . $env['REMOTE_PORT'];

        return $this->matchesString($clientFullAddress) || $this->matchesString($clientFullHostname);
    }

    protected function normalizeValue(string $value): string
    {
        return $this->wildcardToRegexp($value);
    }
}
