<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V10\GoogleAdsClientBuilder;

class GoogleController extends Controller
{
    public function getGoogleAuthentication(
        $refreshToken = "1//04SB7rVeTq2MTCgYIARAAGAQSNwF-L9IrbqvZ34vZWa-rfDFf6Ic9Q8s4avh4M7e7qV0xhUC5fEdPwDTWH8mKRrWTIowEhEPTzpE",
    ) {
        $fromFile = url('google_ads_php.ini');
        // Authentication and creating Client to access Google Ads Api.
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withRefreshToken($refreshToken)
            ->withClientSecret(config('constants.googleAds.clientSecret'))
            ->withClientId(config('constants.googleAds.clientId'))
            ->build();

        // Build client.
        $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile($fromFile)
            ->withOAuth2Credential($oAuth2Credential)
            ->withLoginCustomerId('ssdsdsd')
            ->build();

        return $googleAdsClient;
    }

    public function redirectUri(Request $request)
    {
        dd($request);
    }
}
