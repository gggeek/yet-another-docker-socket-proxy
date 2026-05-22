<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use YADSP\Filter\FilterChain;
use YADSP\Filter\Firewall;
use YADSP\Filter\Tracer;
use YADSP\Logger\ErrorLogger;
use YADSP\Logger\FileLogger;
use YADSP\Logger\FrankenPHPLogger;
use YADSP\Proxy;

if (array_key_exists('YADSP_LOG_FILE', $_SERVER) && trim($_SERVER['YADSP_LOG_FILE']) !== '') {
    $logger = new FileLogger($_SERVER['YADSP_LOG_FILE'], $_SERVER['YADSP_LOG_FILE'] ?? 'warning');
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

$config = array_key_exists('YADSP_CONFIG', $_SERVER) ? trim($_SERVER['YADSP_CONFIG']) : '';
$configFile = array_key_exists('YADSP_CONFIG_FILE', $_SERVER) ? trim($_SERVER['YADSP_CONFIG_FILE']) : '';
if ($configFile !== '') {
    if ($config !== '') {
        throw new \Exception("Can not use at the same time env vars YADSP_CONFIG and YADSP_CONFIG_FILE");
    }
    $filter = Firewall::fromConfigFile($configFile, $logger);
} else {
    $filter = Firewall::fromConfigString($config, $logger);
}

if (array_key_exists('YADSP_TRACE_FILE', $_SERVER) && trim($_SERVER['YADSP_TRACE_FILE']) !== '') {
    $filter = new FilterChain([new Tracer($_SERVER['YADSP_TRACE_FILE']), $filter]);
}

$proxy = new Proxy($filter, $upstream, null, $logger);

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
