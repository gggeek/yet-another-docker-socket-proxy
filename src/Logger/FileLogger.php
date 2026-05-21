<?php
declare(strict_types=1);

namespace DPFP\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Sends log messages to a file
 */
class FileLogger extends AbstractLogger
{
    //protected $format;
    protected string $fileName;

    use ConditionalLoggerTrait;

    public function __construct(string $fileName, string $level = LogLevel::WARNING /*, string $format=''*/)
    {
        $this->fileName = $fileName;
        $this->setLevel($level);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ($this->isHandling($level)) {
            file_put_contents($this->fileName, $this->formatMessage($level, $message, $context) . "\n", FILE_APPEND);
        }
    }

    protected function formatMessage($level, string|\Stringable $message, array $context = []): string
    {
/// @todo... format timestamp, add context data
        return '[' . microtime(true) . '] ' . ucfirst($level) . ": $message";
    }
}
