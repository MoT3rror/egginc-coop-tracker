# egginc-coop-tracker

## Development notes

Currently built with Laravel 8.0 and PHP 7.4.3 . Some auxilary functions are implemented with nodejs.

A supervisor (http://supervisord.org/) instance runs on the server. It keeps both discord.js up and Laraval Horizon. Horizon is only used for the reminders.

The guild roles need to be setup in the database. Currently this can't be done through bot or website. There is 3 flags. is_admin controls what roles can interact with write interactions. show_members_on_roster controls what users will show on the roster screens. show_role controls the roles that are shown for that user.

Add Bot URL:
https://discordapp.com/oauth2/authorize?client_id=741337162762420284&scope=applications.commands&permissions=0

----------

### Sail

It is convinient to start the app server with Laravel Sail  
https://laravel.com/docs/8.x/sail

Example of running a command (i.e. composer install) using the stack when you don't have the stack locally installed:

```
docker run --rm \
    -v $(pwd):/opt \
    -w /opt \
    laravelsail/php74-composer:latest \
    composer install
```

Start containers:

``vendor/bin/sail up``

Shell to app server:

``vendor/bin/sail shell``

Update PHP libraries

``vendor/bin/sail composer install``

Apply migrations to DB

``vendor/bin/sail artisan migrate``

-----------

### Env config

for discord bot

```
DISCORD_BOT_TOKEN=
DISCORD_API_URL=

DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
```

for changing ports of docker containers

```
FORWARD_DB_PORT=3308
APP_PORT=6000
```

-------

### Testing

https://laravel.com/docs/8.x/testing

``vendor/bin/sail test``

or

``php artisan test``

without sail
