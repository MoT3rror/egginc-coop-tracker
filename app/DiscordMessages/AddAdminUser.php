<?php
namespace App\DiscordMessages;

use Illuminate\Support\Arr;

class AddAdminUser extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            return '@user required or ID of uesr';
        }
        $user = $this->cleanAt($parts['1']);

        if (!is_array($this->guild->admin_users)) {
            $this->guild->admin_users = [];
        }
        
        if (!in_array($user, $this->guild->admin_users)) {
            $this->guild->admin_users = Arr::add($this->guild->admin_users, count($this->guild->admin_users), $user);
            $this->guild->save();
        }

        return 'User #' . $user . ' added as admin.';
    }

    public function help(): string
    {
        return '{@user or user ID} - Give user admin abilities.';
    }
}
