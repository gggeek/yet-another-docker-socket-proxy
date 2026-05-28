<?php
declare(strict_types=1);

namespace YADSP\Matcher;

interface MatcherInterface
{
    public function matches(...$items): bool;
}
