<?php

namespace App\Http\Controllers;

use Exception;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V13\GoogleAdsClientBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PulkitJalan\Google\Facades\Google;

class GoogleApiDataController extends Controller
{
    public static function getGoogleAuthentication($refreshToken, $customerId)
    {
        // Authentication and creating Client to access Google Ads Api.
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withRefreshToken($refreshToken)
            ->withClientSecret('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')
            ->withClientId('539486198246-8v6ublp3r0rouhdmue9qn5fi1i9i2m93.apps.googleusercontent.com')
            ->build();

        // Build client.
        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken('HvjQFmg2Hv_sURvM1a-b2g')
            ->withLoginCustomerId($customerId)
            ->build();

        return $googleAdsClient;
    }

    public function getApiData()
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withRefreshToken(Auth::user()->refresh_token)
            ->withClientSecret('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')
            ->withClientId('539486198246-8v6ublp3r0rouhdmue9qn5fi1i9i2m93.apps.googleusercontent.com')
            ->build();

        // Build client.
        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken('HvjQFmg2Hv_sURvM1a-b2g')
            ->build();

        $customerServiceClient = $googleAdsClient->getCustomerServiceClient();
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        $accessibleCustomers = $customerServiceClient->listAccessibleCustomers();
        $allData = [];
        foreach ($accessibleCustomers->getResourceNames() as $resourceName) {
            $customerId = substr($resourceName, (strrpos($resourceName, '/') ?: -1) + 1);

            $query = 'SELECT customer.id, customer_client.id, customer_client.descriptive_name, customer_client.currency_code, customer_client.status FROM customer_client WHERE customer_client.manager != TRUE AND customer_client.test_account != TRUE AND customer_client.hidden != TRUE';

            try {
                $stream = $googleAdsServiceClient->search($customerId, $query);
            } catch (Exception $ex) {
                continue;
            }

            if ($stream) {
                foreach ($stream->iterateAllElements() as $googleAdsRow) {

                    $customerClient = $googleAdsRow->getCustomerClient();
                    $customer = $googleAdsRow->getCustomer();
                    $googleAdsClientNew = $this->getGoogleAuthentication(Auth::user()->refresh_token, $customer->getId());

                    $thisMonth = 'SELECT metrics.clicks, metrics.cost_micros, metrics.impressions, metrics.average_cpc FROM campaign';

                    $response = null;
                    try {
                        $response = $googleAdsClientNew->getGoogleAdsServiceClient()->search(
                            $customerClient->getId(),
                            $thisMonth,
                        );

                    } catch (Exception $ex) {
                        continue;
                    }

                    foreach ($response->iterateAllElements() as $campaign) {
                        print_r('<pre/>');
                        print_r("Customer Id: " . $customer->getId());
                        print_r('<pre/>');
                        print_r("Customer Client Id: " . $customerClient->getId());
                        print_r('<pre/>');
                        print_r("Clicks: " . $campaign->getMetrics()->getClicks());
                        print_r('<pre/>');
                        print_r("Cost: " . $campaign->getMetrics()->getCostMicros());
                        print_r('<pre/>');
                        print_r("Impressions: " . $campaign->getMetrics()->getImpressions());
                        print_r('<pre/>');
                        print_r("Average CPC: " . $campaign->getMetrics()->getAverageCpc());
                    }
                }
            }
        }
    }
}
