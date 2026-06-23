<?php

namespace Orlinkzz\Waha\Message;

class OutgoingMessage
{
    public function __construct(
        public readonly string $chatId,
        public readonly string $text,
        public readonly ?string $session = null,
        public readonly ?array $quotedMessageId = null,
    ) {}

    /**
     * Create from phone number (auto-format to WhatsApp chatId)
     */
    public static function to(string $phone, string $text): self
    {
        $chatId = self::formatPhone($phone);
        return new self(chatId: $chatId, text: $text);
    }

    public static function formatPhone(string $phone): string
    {
        // Strip non-numeric, remove leading +
        $clean = preg_replace('/[^0-9]/', '', $phone);
        return $clean . '@c.us';
    }

    public function withSession(string $session): self
    {
        return new self(
            chatId: $this->chatId,
            text: $this->text,
            session: $session,
            quotedMessageId: $this->quotedMessageId,
        );
    }

    /**
     * Personalize message — replace {name} placeholder and add random spaces
     * to make each message look unique (anti-spam fingerprinting)
     */
    public function personalize(string $name, bool $randomSpaces = true): self
    {
        $text = str_replace('{name}', $name, $this->text);

        if ($randomSpaces) {
            // Insert zero-width spaces randomly to vary fingerprint
            $words = explode(' ', $text);
            $text = implode(
                str_repeat(' ', rand(1, 2)),
                $words
            );
        }

        return new self(
            chatId: $this->chatId,
            text: $text,
            session: $this->session,
            quotedMessageId: $this->quotedMessageId,
        );
    }
}
