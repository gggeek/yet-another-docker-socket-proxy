<?php
declare(strict_types=1);

namespace YADSP\Filter\Bidirectional;

use YADSP\Filter\Request\RequestFilterInterface;
use YADSP\Filter\Response\ResponseFilterInterface;

/**
 * A custom take on Psr\Http\Server\MiddlewareInterface.
 * In this case it is the RequestHandler running a chain of Filters, instead of the Filters getting the handler injected.
 */
interface BidirectionalFilterInterface extends RequestFilterInterface, ResponseFilterInterface
{
}
