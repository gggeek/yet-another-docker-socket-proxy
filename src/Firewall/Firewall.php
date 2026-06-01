<?php
declare(strict_types=1);

namespace YADSP\Firewall;

use YAWAF\Core\Firewall\Firewall as baseFirewall;

/**
 * The class doing the actual filtering of Requests and Responses
 */
class Firewall extends baseFirewall
{
    public const DefaultFallbackConfiguration = [
        'req_match' => [
            'url' => '/_ping', // /version gets disabled out of the box - in case the version number might be useful to attackers...
            'http_method' => ['GET', 'HEAD'],
        ],
        'req_filters' => [],
        'req_action' => 'allow',
        'resp_match' => ['always' => true],
        'resp_action' => 'allow',
        'resp_filters' => [],
    ];
}
