<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

//use DPFP\Logger\ApacheLogger;
use DPFP\Logger\ErrorLogger;
use DPFP\Logger\FrankenPHPLogger;
use DPFP\Proxy;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

$upstream = Proxy::DEFAULT_UPSTREAM;
if (array_key_exists('DOCKER_HOST', $_SERVER) && trim($_SERVER['DOCKER_HOST']) !== '') {
    $upstream = $_SERVER['DOCKER_HOST'];
}
/// @todo... get the filtering config from $_SERVER
$config = [];

if (array_key_exists('DPFP_LOG', $_SERVER) && trim($_SERVER['DPFP_LOG']) !== '') {
    $logger = new \DPFP\Logger\FileLogger($_SERVER['DPFP_LOG'], $_SERVER['DFP_LOG_LEVEL'] ?? 'warning');
} else {
    if (function_exists('frankenphp_log')) {
        $logger = new FrankenPHPLogger();
    // this one is too niche to be on by default :-D
    //} elseif (function_exists('apache_note')) {
    //    $logger = new ApacheLogger();
    } else {
        $logger = new ErrorLogger();
    }
}

$proxy = (new Proxy($upstream, null, $logger))
    ->setConfiguration($config);

$emitter = new SapiEmitter();

$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);

$serverRequest = $creator->fromGlobals();
$response = $proxy->handle($serverRequest);
$emitter->emit($response);
