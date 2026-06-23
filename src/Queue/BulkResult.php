<?php

namespace Orlinkzz\Waha\Queue;

use Orlinkzz\Waha\Message\OutgoingMessage;

class BulkResult
{
    private array $successes = [];
    private array $failures  = [];

    public function __construct(public readonly int $total) {}

    public function addSuccess(OutgoingMessage $message, array $response): void
    {
        $this->successes[] = [
            'chatId'   => $message->chatId,
            'text'     => $message->text,
            'response' => $response,
        ];
    }

    public function addFailure(OutgoingMessage $message, string $error): void
    {
        $this->failures[] = [
            'chatId' => $message->chatId,
            'text'   => $message->text,
            'error'  => $error,
        ];
    }

    public function successCount(): int
    {
        return count($this->successes);
    }

    public function failureCount(): int
    {
        return count($this->failures);
    }

    public function successes(): array
    {
        return $this->successes;
    }

    public function failures(): array
    {
        return $this->failures;
    }

    public function hasFailures(): bool
    {
        return count($this->failures) > 0;
    }

    public function toArray(): array
    {
        return [
            'total'     => $this->total,
            'success'   => $this->successCount(),
            'failed'    => $this->failureCount(),
            'successes' => $this->successes,
            'failures'  => $this->failures,
        ];
    }
}
