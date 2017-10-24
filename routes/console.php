<?php

use Illuminate\Foundation\Inspiring;
use App\Services\Platform\EncryptionService;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('getEncryptDbPass {data}', function ($data) {
    $encryptedData = base64_encode(EncryptionService::privateEncrypt($data));
    $this->info($encryptedData);
});