<?php
declare(strict_types=1);

namespace YADSP;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use YADSP\Logger\PrivateLoggerTrait;

class Proxy implements RequestHandlerInterface, LoggerAwareInterface
{
    const DEFAULT_UPSTREAM = '/var/run/docker.sock';

    protected string $upstream;
    protected ?ClientInterface $client;
    protected FilterInterface $filter;

    use LoggerAwareTrait;
    use PrivateLoggerTrait;

    /**
     * @param FilterInterface $filter
     * @param string $upstream
     * @param ClientInterface|SocketClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @throws \Exception
     */
    public function __construct(FilterInterface $filter, string $upstream = self::DEFAULT_UPSTREAM, ClientInterface|SocketClientInterface|null $httpClient = null, LoggerInterface|null $logger = null)
    {
        // set first the logger
        $this->logger = $logger;
        $this->client = $httpClient;
        $this->filter = $filter;
        $this->setUpstream($upstream);
    }

    /**
     * @param string $upstream
     * @return mixed
     * @throws \Exception
     * @todo use more specific exceptions
     * @todo add support for 'tcp:' upstreams
     */
    protected function setUpstream(string $upstream)
    {
        $upstream = trim($upstream);
        if ($upstream === '') {
            throw new \Exception('Empty upstream passed in');
        }
        if (str_starts_with($upstream, '/')) {
            $this->upstream = $upstream;
            if (!$this->client) {
                $this->client = new Psr18Client(HttpClient::create(['bindto' => $upstream]));
            } else {
                if (!$this->client instanceof SocketClientInterface)
                {
                    throw new \Exception('The passed in HTTP Client does not support socket upstreams');
                }
                $this->client->bindTo($upstream);
            }
            $this->debug("Proxying '$upstream' socket upstream");
            return;
        }
        if (str_starts_with($upstream, 'tcp://')) {
            $this->upstream = $upstream;
            if (!$this->client) {
                $this->client = new Psr18Client(HttpClient::create());
            }
            $this->debug("Proxying '$upstream' tcp upstream");
            return;
        }
        throw new \Exception('Upstream not supported. Only unix sockets (paths starting with "/") and tcp sockets (urls starting with "tcp://") are');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->debug("Received request: " . $this->request2Log($request));

        $filteredRequest = $this->filter->filterRequest($request);
        if (!$filteredRequest) {
            // Q: should we pass in $request or $filteredRequest?
            return $this->deniedResponse($request);
        }
        if ($filteredRequest instanceof ResponseInterface) {
            return $filteredRequest;
        }
        $response = $this->forward($filteredRequest);
        return $this->filter->filterResponse($response, $request);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function forward(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $client = $this->client;
            // avoid dns resolution, in case the http request we get uses a hostname
/// @todo... we are only doing this for unix-socket upstreams, but we should probably do this as well for tcp ones
            if (str_starts_with($this->upstream, '/') && method_exists($this->client, 'withOptions')) {
                $host = $request->getHeaderLine('Host');
                /// @todo... match also IPV6 addresses (with optional port too!), see https://www.ietf.org/rfc/rfc2732.txt
                if (!preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(?::[0-9]{1,5})?$/', $host)) {
                    $host = explode(':', $host, 2);
                    $host = $host[0];
                    $client = $this->client->withOptions([
                        'resolve' => [$host => '127.0.0.1']
                    ]);
                }
            }
            $response = $client->sendRequest($request);
            $this->debug("Upstream returned HTTP/" . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' .
                $response->getReasonPhrase());
            return $response;
        } catch(ClientExceptionInterface $e) {
            return $this->errorResponse($request, $e);
        }
    }

    /**
     * Generates an "access denied" response
     * @return ResponseInterface
     */
    protected function deniedResponse(ServerRequestInterface $request): ResponseInterface
    {
        $this->debug("Access denied for request: " . $this->request2Log($request));
        // Mimic what the Docker daemon returns by default for not-accepted requests - bt give a specific error text
        // (docker says "page not found" for 404s)
        return new Response(404, ['content-type' => 'application/json'], json_encode(['message' => 'access denied']));
    }

    /**
     * Generates an "error happened" response
     * @return ResponseInterface
     */
    protected function errorResponse(ServerRequestInterface $request, \Exception|null $e = null): ResponseInterface
    {
        $this->warning('Upstream connection error for request: '  . $this->request2Log($request) . ' Error:' . $e->getMessage());
/// @todo... make sure we mimic correctly what the Docker daemon returns by default for failed requests (how can we trigger one?)
        return new Response(500, ['content-type' => 'application/json'], json_encode(['message' => 'error ' . $e->getCode()]));
    }

    // *** Logging ***

    protected function request2Log(ServerRequestInterface $request): string
    {
        return $request->getMethod() . ' ' . $request->getUri();
    }
}
