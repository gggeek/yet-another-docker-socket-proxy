<?php
declare(strict_types=1);

namespace DPFP;

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

class Proxy implements RequestHandlerInterface, LoggerAwareInterface
{
    const DEFAULT_UPSTREAM = '/var/run/docker.sock';

    protected string $upstream;
    protected array $config;
    protected ?ClientInterface $client;

    use LoggerAwareTrait;

    /**
     * @param string $upstream
     * @param ClientInterface|null $httpClient
     * @param SocketClientInterface|null $socketClient
     * @param LoggerInterface|null $logger
     * @throws \Exception
     */
    public function __construct(string $upstream = self::DEFAULT_UPSTREAM, ClientInterface|SocketClientInterface|null $httpClient = null, LoggerInterface|null $logger = null)
    {
        $this->logger = $logger;
        $this->client = $httpClient;
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
        if (str_starts_with($upstream, 'http://') || str_starts_with($upstream, 'https://')) {
            $this->upstream = $upstream;
            if (!$this->client) {
                $this->client = new Psr18Client(HttpClient::create());
            }
            $this->debug("Proxying '$upstream' http upstream");
            return;
        }
        throw new \Exception('Upstream not supported. Only sockets (paths starting with "/") and http endpoints (urls starting with "http://" or "https://") are');
    }

    /**
     * @param array $config
     * @return Proxy
     * @throws \Exception
     */
    public function setConfiguration(array $config): Proxy
    {
        $this->config = $this->validateConfiguration($config);
        return $this;
    }

    /**
     * @param array $config
     * @return void
     * @throws \Exception
     */
    protected function validateConfiguration(array $config): array
    {
        foreach($config as $clientIp => $rules) {
/// @todo validate more...
            if (!is_array($rules)) {
                throw new \Exception("Bad configuration: rules for $clientIp should be an array");
            }
        }

        if (isset($config['*'])) {
/// @todo... check that this is the last filter
            $config['*'] = $config['*'] + $this->defaultFilter();
        } else {
            $config['*'] = $this->defaultFilter();
        }

        return $config;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->debug("Received request: " . $this->request2Log($request));

        if ($this->isAccepted($request)) {
            $response = $this->forward($request);
            return $this->filterResponse($response, $request);
        }
        return $this->deniedResponse($request);
    }

    protected function isAccepted(ServerRequestInterface $request): bool
    {
        foreach($this->config as $matchFilter => $rules) {
            if ($this->matchesRequest($matchFilter, $rules, $request)) {
                $this->debug("Filter '$matchFilter' matched request: " . $this->request2Log($request));
                return true;
            }
        }
        $this->debug("No filter matched request: " . $this->request2Log($request));
        return false;
    }

    protected function matchesRequest(string $matchFilter, array $rules, ServerRequestInterface $request): bool
    {
/// @todo...
        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function forward(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $client = $this->client;
            // avoid dns resolution, in case the request we get uses a hostname
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

    protected function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
/// @todo...
        return $response;
    }

    /**
     * Generates an "access denied" response
     * @return ResponseInterface
     */
    protected function deniedResponse(ServerRequestInterface $request): ResponseInterface
    {
        $this->debug("Access denied for request: " . $this->request2Log($request));
/// @todo... mimic what the Docker daemon returns by default for not-accepted requests
        return new Response(404, ['content-type' => 'application/json'], json_encode(['message' => 'access denied']));
    }

    /**
     * Generates an "error happened" response
     * @return ResponseInterface
     */
    protected function errorResponse(ServerRequestInterface $request, \Exception|null $e = null): ResponseInterface
    {
        $this->warning('Upstream connection error for request: '  . $this->request2Log($request) . ' Error:' . $e->getMessage());
/// @todo... mimic what the Docker daemon returns by default for failed requests
        return new Response(500, ['content-type' => 'application/json'], json_encode(['message' => 'error ' . $e->getCode()]));
    }

    /**
     * Returns the default filter applied to all clients - let ping requests through
     * @return array
     * @todo allow access to /events?
     */
    protected function defaultFilter(): array
    {
        return [
            '/_ping' => [
                'verbs' => ['GET', 'HEAD'],
            ],
            /// @todo should we disable this? The version number might be useful to attackers...
            '/version' => [
                'verbs' => ['GET'],
            ],
        ];
    }

    // *** Logging ***

    protected function request2Log(ServerRequestInterface $request): string
    {
        return $request->getMethod() . ' ' . $request->getUri();
    }

    /**
     * System is unusable.
     */
    protected function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     */
    protected function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     * Example: Application component unavailable, unexpected exception.
     */
    protected function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    protected function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     */
    protected function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    protected function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     * Example: User logs in, SQL logs.
     */
    protected function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    protected function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     * @param mixed $level
     * @throws \Psr\Log\InvalidArgumentException
     */
    protected function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger?->log($level, $message, $context);
    }
}
