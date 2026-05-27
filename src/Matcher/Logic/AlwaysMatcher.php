<?php
declare(strict_types=1);

namespace YADSP\Matcher;

use YADSP\MatcherInterface;

class AlwaysMatcher implements MatcherInterface
{
    public function matches(...$items): bool
    {
        return true;
    }
}
