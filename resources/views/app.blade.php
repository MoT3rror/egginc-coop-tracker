<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <script data-goatcounter="https://mot3rror.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
        <script src="{{ mix('/js/app.js') }}" defer></script>
        <title>MoT3rror Egg Inc PlayGround</title>
        <link href="{{ mix('/css/app.css') }}" rel="stylesheet" />
        <script>window.Laravel = {csrfToken: '{{ csrf_token() }}'}</script>
    </head>
    <body>
        @inertia
    </body>
</html>