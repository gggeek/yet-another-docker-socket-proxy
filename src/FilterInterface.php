<?php
declare(strict_types=1);

namespace YADSP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface FilterInterface
{
    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface|false;

    public function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface;
}
