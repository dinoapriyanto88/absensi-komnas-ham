<?php

namespace App\Http\Controllers;

use App\Support\Captcha;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    public function __invoke(): Response
    {
        return response(Captcha::terbitkan(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
