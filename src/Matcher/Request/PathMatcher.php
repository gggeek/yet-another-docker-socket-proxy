<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use YAWAF\Core\Matcher\Request\PathMatcher as BasePathMatcher;

class PathMatcher extends BasePathMatcher
{
    /**
     * @param string|string[] $filter
     * @throws \Exception
     */
    public function __construct(string|array $filter)
    {
        parent::__construct($filter, '(/v[0-9.]+/)?');
    }
}
