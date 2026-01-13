<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyController;


Route::get('/', function () {
    return response()->json('Hello Shopify!');
});


Route::get('/auth', [ShopifyController::class, 'redirectToShopify']);
Route::get('/auth/callback', [ShopifyController::class, 'callback']);

