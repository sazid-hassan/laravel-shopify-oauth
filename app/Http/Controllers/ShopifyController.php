<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShopifyController extends Controller
{
    public function redirectToShopify(Request $request)
    {
        $shop = $request->get('shop');

        $installUrl = "http://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => env('SHOPIFY_API_KEY'),
            'scope' => env('SHOPIFY_SCOPES'),
            'redirect_uri' => env('SHOPIFY_REDIRECT_URI'),
            'state' => csrf_token()
        ]);

        return redirect($installUrl);
    }

    public function callback(Request $request)
    {
        $shop = $request->shop;
        $code = $request->code;

        $response = Http::post("http://{$shop}/admin/oauth/access_token", [
            'client_id' => env('SHOPIFY_API_KEY'),
            'client_secret' => env('SHOPIFY_API_SECRET'),
            'code' => $code
        ]);

        $token = $response['access_token'];

        Shop::updateOrCreate(
            ['shop' => $shop],
            ['access_token' => $token]
        );

        return "Installed successfully for {$shop}";
    }

}
