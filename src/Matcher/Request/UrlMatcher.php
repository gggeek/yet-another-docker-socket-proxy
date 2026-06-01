<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use YAWAF\Core\Matcher\Request\UrlMatcher as BaseUrlMatcher;

class UrlMatcher extends BaseUrlMatcher
{
    /**
     * @param string|string[] $filter
     * @throws \Exception
     */
    public function __construct(string|array $filter)
    {
        parent::__construct($filter, true, true, '(/v[0-9.]+/)?');
    }
}
