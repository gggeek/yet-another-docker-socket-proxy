<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Http\Message\ServerRequestInterface;
use YADSP\Matcher\RegExpListMatcherTrait;
use YADSP\Matcher\StringListMatcherTrait;
use YADSP\MatcherInterface;

class MethodMatcher extends BaseMatcher
{
    use StringListMatcherTrait;

    /**
     * @param string|array $filter
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
        return $this->matchesString($request->getMethod());
    }

    protected function normalizeValue(string $value): string
    {
        return strtoupper(trim($value));
    }
}
