<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Http\Message\ServerRequestInterface;
use YADSP\Matcher\RegExpListMatcherTrait;

class UrlMatcher extends BaseMatcher
{
    use RegExpListMatcherTrait;

    /**
     * @param string|string[] $filter
     * @throws \Exception
     */
    public function __construct(string|array $filter)
    {
        if (is_array($filter)) {
            $this->setAllowedValues(...$filter);
        } else {
            $this->setAllowedValues($filter);
        }
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return $this->matchesString($request->getRequestTarget());
    }

    /// @todo add wildcard support...
    /// @todo add '/vXX/' prefix support...
    /// @todo add support for query strings and anchors
    protected function normalizeValue(string $value): string
    {
        return '^' . preg_quote($value, $this->regexpDelimiter) . '$';
    }
}
