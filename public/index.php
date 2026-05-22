<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DPFP\Logger\ErrorLogger;
use DPFP\Logger\FrankenPHPLogger;
use DPFP\Proxy;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

if (array_key_exists('DPFP_LOG', $_SERVER) && trim($_SERVER['DPFP_LOG']) !== '') {
    $logger = new \DPFP\Logger\FileLogger($_SERVER['DPFP_LOG'], $_SERVER['DFP_LOG_LEVEL'] ?? 'warning');
} else {
    if (function_exists('frankenphp_log')) {
        $logger = new FrankenPHPLogger();
    } else {
        $logger = new ErrorLogger();
    }
}

$upstream = Proxy::DEFAULT_UPSTREAM;
if (array_key_exists('DOCKER_HOST', $_SERVER) && trim($_SERVER['DOCKER_HOST']) !== '') {
    $upstream = $_SERVER['DOCKER_HOST'];
}

$config = array_key_exists('DPFP_CONFIG', $_SERVER) ? trim($_SERVER['DPFP_CONFIG']) : '';
$configFile = array_key_exists('DPFP_CONFIG_FILE', $_SERVER) ? trim($_SERVER['DPFP_CONFIG_FILE']) : '';
if ($configFile !== '') {
    if ($config !== '') {
        throw new \Exception("Can not use at the same time env vars DPFP_CONFIG and DPFP_CONFIG_FILE");
    }
    $config = file_get_contents($configFile);
    if ($config === false) {
        throw new \Exception("Can not read file '$configFile' specified in env var DPFP_CONFIG_FILE");
    }
}
if (trim($config) === '') {
    $logger->warning("No configuration passed in. The proxy will only let trough 'ping' and 'version' API calls");
    $config = [];
} else {
    $config = @json_decode($config, true);
    if (!is_array($config)) {
        throw new \Exception("The configuration passed in is not a valid json array. Error: " . json_last_error_msg());
    }
}

$proxy = (new Proxy($upstream, null, $logger))
    ->setConfiguration($config);

$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);

$emitter = new SapiEmitter();

$handler = function() use($creator, $proxy, $emitter) {
    $serverRequest = $creator->fromGlobals();
    $response = $proxy->handle($serverRequest);
    $emitter->emit($response);
};

if (!array_key_exists('FRANKENPHP_WORKER', $_SERVER) || (int)$_SERVER['FRANKENPHP_WORKER'] == 0) {

    $handler();

} else {

    $maxRequests = (int)($_SERVER['MAX_REQUESTS_PER_WORKER'] ?? 0);
    for ($nbRequests = 0; !$maxRequests || $nbRequests < $maxRequests; ++$nbRequests) {

        // NB: `set_exception_handler` is called only when the worker script ends,
        // which may be unexpected, so we could (should?) catch and handle exceptions inside $handler

        /** @noinspection PhpUndefinedFunctionInspection */
        /** @phpstan-ignore function.notFound */
        $keepRunning = \frankenphp_handle_request($handler);

        // Call the garbage collector to reduce the chances of it being triggered in the middle of a page generation
        /// @todo do this every N requests?
        gc_collect_cycles();

        if (!$keepRunning) break;
    }
}
