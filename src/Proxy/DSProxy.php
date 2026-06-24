<?php
declare(strict_types=1);

namespace YADSP\Proxy;

use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use YAWAF\Core\Filter\Bidirectional\BidirectionalFilterInterface;
use YAWAF\Core\Proxy\FixedUpstreamProxy as BaseProxy;
use YAWAF\Core\UpstreamClient\UpstreamClientInterface;

class DSProxy extends BaseProxy
{
    const DEFAULT_UPSTREAM = '/var/run/docker.sock';

    /**
     * @todo... disallow http/https upstreams?
     * @throws \Exception
     */
    protected function setUpstream(string $upstream, UpstreamClientInterface|null $httpClient = null): UpstreamClientInterface
    {
        return parent::setUpstream($upstream, $httpClient);
    }
}
