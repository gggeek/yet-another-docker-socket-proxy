<?php
declare(strict_types=1);

namespace YADSP\Filter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use YADSP\FilterInterface;
use YADSP\Logger\PrivateLoggerTrait;

/**
 * The class doing the actual filtering of Requests and Responses
 */
class Firewall implements FilterInterface, LoggerAwareInterface
{
    protected array $config;

    use LoggerAwareTrait;
    use PrivateLoggerTrait;

    /**
     * @param string $configuration
     * @param LoggerInterface|null $logger
     * @return static
     * @throws \Exception
     */
    public static function fromConfigString(string $configuration, LoggerInterface|null $logger): static
    {
        if (trim($configuration) === '') {
            $config = [];
        } else {
            $config = @json_decode($configuration, true);
            if (!is_array($config)) {
                throw new \Exception("The configuration passed in is not a valid json array. Error: " . json_last_error_msg());
            }
        }
        return new static($config, $logger);
    }

    /**
     * @param string $configurationFile
     * @param LoggerInterface|null $logger
     * @return static
     * @throws \Exception
     */
    public static function fromConfigFile(string $configurationFile, LoggerInterface|null $logger): static
    {
        /// @todo add better error handling as courtesy to the user
        return static::fromConfigString(file_get_contents($configurationFile), $logger);
    }

    /**
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config, LoggerInterface|null $logger = null)
    {
        $this->logger = $logger;
        if (!$config) {
            $this->warning("No configuration passed in. The proxy will only let trough 'ping' and 'version' API calls");
        }
        $this->config = $this->validateConfiguration($config);
    }

    /**
     * @param array $config
     * @return array
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
            $config['*'] = $config['*'] + $this->defaultConfiguration();
        } else {
            $config['*'] = $this->defaultConfiguration();
        }

        return $config;
    }

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface|false
    {
        foreach($this->config as $matchFilter => $rules) {
            if ($this->matchesRequest($matchFilter, $rules, $request)) {
                $this->debug("Filter '$matchFilter' matched request: " . $this->request2Log($request));
                return $request;
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

    public function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
/// @todo...
        return $response;
    }

    /**
     * Returns the default filter applied to all clients - let ping and version requests through
     * @return array
     * @todo allow access to /events?
     */
    protected function defaultConfiguration(): array
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
}
