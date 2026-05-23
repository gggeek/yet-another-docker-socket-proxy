<?php
declare(strict_types=1);

namespace YADSP\Matcher\Request;

use Psr\Http\Message\ServerRequestInterface;
use YADSP\MatcherInterface;

abstract class BaseMatcher implements MatcherInterface, RequestMatcherInterface
{
    public function matches(...$items): bool
    {
        if (count($items) !== 1 || ! $items[0] instanceof ServerRequestInterface) {
            throw new \Exception('Matcher expected a ServerRequestInterface but got instead a ' . gettype($items[0]));
        }

        return $this->matchesRequest($items[0]);
    }

    abstract function matchesRequest(ServerRequestInterface $request): bool;
}
