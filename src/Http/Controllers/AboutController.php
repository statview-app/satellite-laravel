<?php

namespace Statview\Satellite\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class AboutController
{
    public function __invoke()
    {
        Artisan::call('about --json');

        return [
            'data' => json_decode(Artisan::output(), true),
        ];
    }
}