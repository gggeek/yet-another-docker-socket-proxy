<?php
declare(strict_types=1);

namespace YADSP\Matcher;

use YADSP\MatcherInterface;

class OrMatcher implements MatcherInterface
{
    /** @var MatcherInterface[] */
    protected array $matchers = [];

    /**
     * @param MatcherInterface[] $matchers
     */
    public function __construct(array $matchers)
    {
        foreach ($matchers as $matcher) {
            $this->addMatcher($matcher);
        }
    }

    public function addMatcher(MatcherInterface $matcher)
    {
        $this->matchers[] = $matcher;
    }

    public function matches(...$items): bool
    {
        if (!$this->matchers) {
            throw new \Exception('Chain Matcher has not children matchers. Can not test for match');
        }

        foreach ($this->matchers as $matcher) {
            if ($matcher->matches(...$items)) {
                return true;
            }
        }
        return false;
    }
}
