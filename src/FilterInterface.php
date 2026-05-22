<?php
declare(strict_types=1);

namespace YADSP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A custom take on Psr\Http\Server\MiddlewareInterface.
 * In this case it is the RequestHandler running a chain of Filters, instead of the Filters getting the handler injected.
 */
interface FilterInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface|ResponseInterface|false false when the request is black-holed and does not have to
     *         be sent to the server; a synthetic response, in which case the server is also not contacted, or the
     *         request to be sent, possibly tweaked
     */
    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface|false;

    public function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface;
}
