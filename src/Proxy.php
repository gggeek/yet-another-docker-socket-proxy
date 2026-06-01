<?php
declare(strict_types=1);

namespace YADSP;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use YAWAF\Core\Filter\Bidirectional\BidirectionalFilterInterface;
use YAWAF\Core\Proxy as BaseProxy;
use YAWAF\Core\SocketClientInterface;

class Proxy extends BaseProxy
{
    const DEFAULT_UPSTREAM = '/var/run/docker.sock';

    /**
     * @param BidirectionalFilterInterface $filter
     * @param string $upstream
     * @param ClientInterface|SocketClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @throws \Exception
     */
    public function __construct(BidirectionalFilterInterface $filter, string $upstream = self::DEFAULT_UPSTREAM, ClientInterface|SocketClientInterface|null $httpClient = null, LoggerInterface|null $logger = null)
    {
        parent::__construct($filter, $upstream, $httpClient, $logger);
    }

    /**
     * @todo... disallow http/https upstreams?
     * @throws \Exception
     */
    protected function setUpstream(string $upstream): void
    {
        parent::setUpstream($upstream);
    }

    /**
     * Generates an "access denied" response: mimic what the Docker daemon returns by default for not-accepted requests,
     * but give a specific error text.
     * @todo make it easy to change this from config
     * @return ResponseInterface
     */
    protected function deniedResponse(ServerRequestInterface $request): ResponseInterface
    {
        $this->debug("Access denied for request: " . $this->request2Log($request));
        // Mimic what the Docker daemon returns by default for not-accepted requests - but give a specific error text
        // (docker says "page not found" for 404s)
        return new Response(404, ['content-type' => 'application/json'], json_encode(['message' => 'access denied']));
    }

    /**
     * Generates an "error happened" response
     * @todo make it easy to change this from config
     * @return ResponseInterface
     */
    protected function errorResponse(ServerRequestInterface $request, \Exception|null $e = null): ResponseInterface
    {
        $this->warning('Upstream connection error for request: '  . $this->request2Log($request) . ' Error:' . $e->getMessage());
/// @todo... make sure we mimic correctly what the Docker daemon returns by default for failed requests (try to we trigger one...)
        return new Response(500, ['content-type' => 'application/json'], json_encode(['message' => 'error ' . $e->getCode()]));
    }
}
