<?php

namespace App\Http\Controllers;

use App\Support\WebCron;
use Illuminate\Http\Response;

class CronController extends Controller
{
    public function __invoke(): Response
    {
        $result = WebCron::run();

        return response($result['body']."\n", $result['status'])
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }
}
