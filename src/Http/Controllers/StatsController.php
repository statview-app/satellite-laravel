<?php

namespace Statview\Satellite\Http\Controllers;

use Statview\Satellite\Statview;

class StatsController
{
    public function __invoke()
    {
        return [
            'widgets' => Statview::getWidgets(),
        ];
    }
}