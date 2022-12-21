<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DiscordErrorException;
use App\Http\Controllers\Controller;
use Arr;
use Illuminate\Http\Request;

class DiscordMessage extends Controller
{
    private $guildId;

    private $validCommands;

    public function __construct()
    {
        $this->validCommands = app()->make('DiscordMessages');
    }

    public function receive(Request $request): array
    {
        $message = trim(str_replace($request->input('atBotUser'), '', $request->input('content')));
        // $parts = explode(' ', $message); // split on space
        $parts = preg_split('/\r\n|\r|\n| /', $message, -1, PREG_SPLIT_NO_EMPTY); // split on space or new lines
        $command = Arr::get($parts, '0');
        
        try {
            $commandInfo = Arr::get($this->validCommands, $command);

            if (!$commandInfo) {
                throw new DiscordErrorException('Invalid command: ' . $command);
            }

            $class = $commandInfo['class'];
            $object = new $class(
                $request->input('author.id'),
                $request->input('author.username', ''),
                $request->input('channel.guild.id'),
                $request->input('channel.id'),
                $parts
            );
            $object->setChannelType($request->input('channel.type', ''));
            if ($request->input('channel.parentId')) {
                $object->setChannelParent($request->input('channel.parentId', ''));
            }
            return ['message' => $object->message()];
        } catch (DiscordErrorException $e) {
            return ['message' => $e->getMessage()];
        }
    }
}
