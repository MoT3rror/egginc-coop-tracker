<?php
namespace App\DiscordMessages;

use Illuminate\Support\Arr;

class ViewAdminUsers extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        if (!is_array($this->guild->admin_users)) {
            return 'No admin users are set. Admin roles are be managed by website.';
        }
        
        $output = '';
        $first = true;
        foreach ($this->guild->admin_users as $user) {
            if ($first) {
                $first = false;
            } else {
                $output .= PHP_EOL;
            }
            $output .= '- <@' . $user . '> #' . $user;
        }
        return $output;
    }

    public function help(): string
    {
        return 'View admin users.';
    }
}
