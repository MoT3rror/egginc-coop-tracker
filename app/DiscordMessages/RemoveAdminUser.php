<?php
namespace App\DiscordMessages;

use Illuminate\Support\Arr;

class RemoveAdminUser extends Base
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
        
        if (in_array($user, $this->guild->admin_users)) {
            $users = (array) $this->guild->admin_users;
            Arr::forget($users, array_search($user, $users));
            $this->guild->admin_users = $users;
            $this->guild->save();
        }

        return 'User #' . $user . ' removed as admin.';
    }

    public function help(): string
    {
        return '{@user or user ID} - Remove user admin abilities.';
    }
}
