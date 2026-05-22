<?php

namespace YADSP\Filter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use YADSP\FilterInterface;

class Tracer implements FilterInterface
{
    protected $fileName;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface|false
    {
        // We could serialize the request in the `filterResponse` method, bot doing it here means we will have a trace
        // of the request in case there is a fatal error before we get to trace the response
        file_put_contents($this->fileName, $this->serializeRequest($request), FILE_APPEND);
        return $request;
    }

    public function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        file_put_contents($this->fileName, $this->serializeResponse($response) . "--\n", FILE_APPEND);
        return $response;
    }

    protected function serializeRequest(ServerRequestInterface $request): string
    {
        $out = '> ' . $request->getMethod() . ' ' . $request->getRequestTarget() . ' HTTP/' . $request->getProtocolVersion() . "\n";
        foreach ($request->getHeaders() as $name => $values) {
            $out .= '> ' . $name . ": " . implode(", ", $values) . "\n";
        }
        $out .= "> \n";
        $body = (string)$request->getBody();
        if ($body !== '') {
            $out .= $body . "\n";
        }
        return $out;
    }

    protected function serializeResponse(ResponseInterface $response): string
    {
        $out = '< ' . 'HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . "\n";
        foreach ($response->getHeaders() as $name => $values) {
            $out .= '< ' . $name . ": " . implode(", ", $values) . "\n";
        }
        $out .= "< \n";
        $body = (string)$response->getBody();
        if ($body !== '') {
            $out .= $body . "\n";
        }
        return $out;
    }
}
