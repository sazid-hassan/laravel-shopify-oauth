<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class ShopifyController extends Controller
{
    public function redirectToShopify(Request $request)
    {
        $shop = $request->query('shop');

        if (!$shop) {
            abort(400, 'Missing shop parameter');
        }

        $state = csrf_token();
        Session::put('shopify_oauth_state', $state);

        $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => config('services.shopify.key'),
            'scope' => config('services.shopify.scopes'),
            'redirect_uri' => config('services.shopify.redirect_uri'),
            'state' => $state,
        ]);

        return redirect()->away($installUrl);
    }

    public function callback(Request $request)
    {
        $this->verifyHmac($request);

        if ($request->state !== Session::pull('shopify_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $shop = $request->query('shop');
        $code = $request->query('code');

        $response = Http::asForm()->post(
            "https://{$shop}.myshopify.com/admin/oauth/access_token",
            [
                'client_id' => config('services.shopify.key'),
                'client_secret' => config('services.shopify.secret'),
                'code' => $code,
            ]
        );

        $token = $response->json('access_token');

        Shop::updateOrCreate(
            ['shop_domain' => $shop],
            ['access_token' => $token]
        );

        return redirect("/app?shop={$shop}");
    }

    protected function verifyHmac(Request $request)
    {
        $hmac = $request->query('hmac');
        $data = $request->except('hmac', 'signature');

        ksort($data);

        $queryString = urldecode(http_build_query($data));
        $calculatedHmac = hash_hmac(
            'sha256',
            $queryString,
            config('services.shopify.secret')
        );

        if (!hash_equals($hmac, $calculatedHmac)) {
            abort(403, 'Invalid HMAC');
        }
    }
}
