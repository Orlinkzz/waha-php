<?php

namespace Orlinkzz\Waha\Laravel;

use Illuminate\Support\Facades\Facade;
use Orlinkzz\Waha\WahaClient;

/**
 * @method static array reply(string $chatId, string $text, ?string $session = null)
 * @method static array send(\Orlinkzz\Waha\Message\OutgoingMessage $message, bool $antiBanned = true)
 * @method static \Orlinkzz\Waha\Queue\BulkResult sendBulk(array $messages, ?callable $onEach = null, ?callable $onBatchPause = null)
 * @method static array sendText(string $chatId, string $text, ?string $session = null)
 * @method static array sendImage(string $chatId, string $urlOrBase64, string $caption = '', ?string $session = null)
 * @method static array sendFile(string $chatId, string $url, string $filename, ?string $session = null)
 * @method static array sendSeen(string $chatId, ?string $session = null)
 * @method static array startTyping(string $chatId, ?string $session = null)
 * @method static array stopTyping(string $chatId, ?string $session = null)
 * @method static array startSession(?string $session = null)
 * @method static array stopSession(?string $session = null)
 * @method static array getSessionStatus(?string $session = null)
 * @method static array listSessions()
 * @method static array getChats(?string $session = null)
 * @method static array getMessages(string $chatId, int $limit = 20, ?string $session = null)
 *
 * @see WahaClient
 */
class WahaFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'waha';
    }
}
