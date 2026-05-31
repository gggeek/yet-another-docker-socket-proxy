<?php

namespace YADSP\Matcher\Message;

use Psr\Http\Message\MessageInterface;

interface MessageMatcherInterface
{
    public function matchesMessage(MessageInterface $message): bool;
}
