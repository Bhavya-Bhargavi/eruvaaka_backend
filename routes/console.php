<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\MartProductSyncService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('products:sync', function (MartProductSyncService $syncer): void {
    $this->info('Synced '.$syncer->sync().' Eruvaaka Mart products.');
})->purpose('Import products from the Eruvaaka Mart WooCommerce Store API');
