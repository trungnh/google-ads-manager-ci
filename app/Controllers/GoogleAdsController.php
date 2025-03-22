<?php 

namespace App\Controllers;

use App\Models\GoogleAccountModel;
use App\Models\GoogleAdsAccountModel;
use CodeIgniter\Controller;
use Google\Client as GoogleClient;
use Google\Ads\GoogleAds\Lib\V12\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V12\GoogleAdsException;
use Google\Ads\GoogleAds\Util\V12\ResourceNames;
use Google\Ads\GoogleAds\V12\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V12\Services\GoogleAdsServiceClient;

class GoogleAdsController extends Controller
{
    private $client;
    
    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(getenv('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
        
        $this->googleAccountModel = new GoogleAccountModel();
        $this->googleAdsAccountModel = new GoogleAdsAccountModel();
    }
    
    public function accounts()
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $userId = $session->get('user_id');
        $googleAccounts = $this->googleAccountModel->getAccountsByUserId($userId);
        
        $data = [
            'title' => 'Google Ads Accounts',
            'google_accounts' => $googleAccounts
        ];
        
        return view('google_ads/accounts', $data);
    }
    
    public function syncAccounts($googleAccountId)
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $userId = $session->get('user_id');
        $googleAccount = $this->googleAccountModel->getAccountByGoogleId($userId, $googleAccountId);
        
        if (!$googleAccount) {
            return redirect()->to('/google/ads/accounts')->with('error', 'Google account not found');
        }
        
        try {
            // Set up the OAuth2 credentials
            $accessToken = json_decode($googleAccount['access_token'], true);
            $this->client->setAccessToken($accessToken);
            
            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    $newAccessToken = $this->client->getAccessToken();
                    
                    $this->googleAccountModel->update($googleAccount['id'], [
                        'access_token' => json_encode($newAccessToken),
                        'token_expiry' => date('Y-m-d H:i:s', time() + $newAccessToken['expires_in'])
                    ]);
                } else {
                    return redirect()->to('/google/connect')->with('error', 'OAuth token expired. Please reconnect your Google account.');
                }
            }
            
            // Create a Google Ads API client
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId(getenv('GOOGLE_CLIENT_ID'))
                ->withClientSecret(getenv('GOOGLE_CLIENT_SECRET'))
                ->withRefreshToken($this->client->getRefreshToken())
                ->build();
            
            $googleAdsClient = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken(getenv('GOOGLE_ADS_DEVELOPER_TOKEN'))
                ->withLoginCustomerId(getenv('GOOGLE_ADS_LOGIN_CUSTOMER_ID')) // Optional, for MCC accounts
                ->build();
            
            // Get the CustomerServiceClient
            $customerServiceClient = $googleAdsClient->getCustomerServiceClient();
            
            // Get the list of accessible customers
            $accessibleCustomers = $customerServiceClient->listAccessibleCustomers();
            
            foreach ($accessibleCustomers->getResourceNames() as $resourceName) {
                $customerId = str_replace('customers/', '', $resourceName);
                
                // Get the customer details
                try {
                    $customer = $customerServiceClient->getCustomer($resourceName);
                    
                    // Check if the customer already exists in the database
                    $existingCustomer = $this->googleAdsAccountModel->where('google_account_id', $googleAccount['id'])
                                                                 ->where('customer_id', $customerId)
                                                                 ->first();
                    
                    if ($existingCustomer) {
                        // Update the existing customer
                        $this->googleAdsAccountModel->update($existingCustomer['id'], [
                            'name' => $customer->getDescriptiveName(),
                        ]);
                    } else {
                        // Create a new customer
                        $this->googleAdsAccountModel->save([
                            'google_account_id' => $googleAccount['id'],
                            'customer_id' => $customerId,
                            'name' => $customer->getDescriptiveName(),
                        ]);
                    }
                } catch (\Exception $e) {
                    // Some accounts might not be accessible
                    log_message('error', 'Error accessing customer: ' . $customerId . ', Error: ' . $e->getMessage());
                    continue;
                }
            }
            
            return redirect()->to('/google/ads/accounts')->with('success', 'Google Ads accounts synced successfully');
        } catch (\Exception $e) {
            log_message('error', 'Error syncing Google Ads accounts: ' . $e->getMessage());
            return redirect()->to('/google/ads/accounts')->with('error', 'Failed to sync Google Ads accounts: ' . $e->getMessage());
        }
    }
    
    public function campaigns($accountId)
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $adsAccount = $this->googleAdsAccountModel->find($accountId);
        
        if (!$adsAccount) {
            return redirect()->to('/google/ads/accounts')->with('error', 'Google Ads account not found');
        }
        
        $googleAccount = $this->googleAccountModel->find($adsAccount['google_account_id']);
        
        try {
            // Set up the OAuth2 credentials
            $accessToken = json_decode($googleAccount['access_token'], true);
            $this->client->setAccessToken($accessToken);
            
            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    $newAccessToken = $this->client->getAccessToken();
                    
                    $this->googleAccountModel->update($googleAccount['id'], [
                        'access_token' => json_encode($newAccessToken),
                        'token_expiry' => date('Y-m-d H:i:s', time() + $newAccessToken['expires_in'])
                    ]);
                } else {
                    return redirect()->to('/google/connect')->with('error', 'OAuth token expired. Please reconnect your Google account.');
                }
            }
            
            // Create a Google Ads API client
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId(getenv('GOOGLE_CLIENT_ID'))
                ->withClientSecret(getenv('GOOGLE_CLIENT_SECRET'))
                ->withRefreshToken($this->client->getRefreshToken())
                ->build();
            
            $googleAdsClient = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken(getenv('GOOGLE_ADS_DEVELOPER_TOKEN'))
                ->build();
            
            // Get the GoogleAdsServiceClient
            $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
            
            // Build the query
            $query = "
                SELECT
                    campaign.id,
                    campaign.name,
                    campaign.status,
                    campaign.advertising_channel_type,
                    campaign.bidding_strategy_type,
                    campaign.campaign_budget,
                    campaign.target_cpa.target_cpa_micros,
                    campaign.target_roas.target_roas,
                    metrics.impressions,
                    metrics.clicks,
                    metrics.cost_micros,
                    metrics.average_cpc,
                    metrics.ctr,
                    metrics.all_conversions,
                    metrics.all_conversions_value,
                    metrics.all_conversions_value_per_cost,
                    metrics.cost_per_all_conversions,
                    metrics.all_conversions_rate
                FROM campaign
                WHERE campaign.status != 'REMOVED'
                ORDER BY campaign.name
            ";
            
            // Execute the query
            $response = $googleAdsServiceClient->search(
                $adsAccount['customer_id'],
                $query,
                ['pageSize' => 1000]
            );
            
            // Process the results
            $campaigns = [];
            foreach ($response->iterateAllElements() as $googleAdsRow) {
                $campaign = $googleAdsRow->getCampaign();
                $metrics = $googleAdsRow->getMetrics();
                
                // Fetch campaign budget
                $budgetResourceName = $campaign->getCampaignBudget();
                $budgetId = substr($budgetResourceName, strrpos($budgetResourceName, '/') + 1);
                
                $campaignData = [
                    'id' => $campaign->getId(),
                    'name' => $campaign->getName(),
                    'status' => $campaign->getStatus(),
                    'channel_type' => $campaign->getAdvertisingChannelType(),
                    'bidding_strategy' => $campaign->getBiddingStrategyType(),
                    'budget_id' => $budgetId,
                    'target_cpa' => $campaign->getTargetCpa() ? $campaign->getTargetCpa()->getTargetCpaMicros() / 1000000 : null,
                    'target_roas' => $campaign->getTargetRoas() ? $campaign->getTargetRoas()->getTargetRoas() : null,
                    'impressions' => $metrics->getImpressions(),
                    'clicks' => $metrics->getClicks(),
                    'cost' => $metrics->getCostMicros() / 1000000,
                    'average_cpc' => $metrics->getAverageCpc() ? $metrics->getAverageCpc() / 1000000 : 0,
                    'ctr' => $metrics->getCtr() * 100,
                    'conversions' => $metrics->getAllConversions(),
                    'conversion_value' => $metrics->getAllConversionsValue(),
                    'roas' => $metrics->getAllConversionsValuePerCost(),
                    'cost_per_conversion' => $metrics->getCostPerAllConversions() ? $metrics->getCostPerAllConversions() : 0,
                    'conversion_rate' => $metrics->getAllConversionsRate() * 100,
                ];
                
                $campaigns[] = $campaignData;
            }
            
            $data = [
                'title' => 'Google Ads Campaigns',
                'adsAccount' => $adsAccount,
                'googleAccount' => $googleAccount,
                'campaigns' => $campaigns
            ];
            
            return view('google_ads/campaigns', $data);
        } catch (GoogleAdsException $e) {
            foreach ($e->getGoogleAdsFailure()->getErrors() as $error) {
                log_message('error', 'Google Ads API error: ' . $error->getMessage());
            }
            return redirect()->to('/google/ads/accounts')->with('error', 'Failed to fetch campaigns: ' . $e->getMessage());
        } catch (\Exception $e) {
            log_message('error', 'Error fetching campaigns: ' . $e->getMessage());
            return redirect()->to('/google/ads/accounts')->with('error', 'Failed to fetch campaigns: ' . $e->getMessage());
        }
    }
}