<?php

namespace App\Support\Telegram\Handlers;

use App\Models\TelegramUpdate;
use App\Services\File\File;
use App\Support\Telegram\Contracts\Handler;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class FileSaveHandler extends Handler
{
    public function __construct(
        private readonly File $fileService,
        private readonly Api $telegram
    ) {}

    public function handle(Update $update, TelegramUpdate $telegramUpdate): void
    {
        $message = $update->getMessage();
        $document = $message->getDocument();
        $fileId = $document->getFileId();
        $fileName = $document->getFileName();
        $chatId = $message->getChat()->getId();

        try {
            $link = $this->fileService->saveFile($telegramUpdate, $fileId, $fileName);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Your file link: " . $link . PHP_EOL . 'This link will be available in one hour.',
            ]);

            $this->next($update, $telegramUpdate);
        } catch (FileDoesNotExist $e) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "The file is not available",
            ]);
        } catch (FileIsTooBig $e) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "The file size exceeds the maximum limit of 500 MB.",
            ]);
        }
    }
}
