<?php

namespace App\Http\Controllers;

use App\Models\ShortLink as ShortLinkModel;

class ShortLink extends Controller
{
    public function link($code)
    {
        $find = ShortLinkModel::where('code', $code)->firstOrFail();
   
        return redirect($find->link);
    }
}
