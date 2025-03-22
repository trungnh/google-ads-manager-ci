<?php 

namespace App\Services;

use Google\Ads\GoogleAds\Lib\V12\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V12\GoogleAdsException;
use Google\Ads\GoogleAds\Util\V12\ResourceNames;

class GoogleAdsService
{
    private $googleAdsClient;
    
    public function __construct($refreshToken)
    {
        // Create a Google Ads API client
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId(getenv('GOOGLE_CLIENT_ID'))
            ->withClientSecret(getenv('GOOGLE_CLIENT_SECRET'))
            ->withRefreshToken($refreshToken)
            ->build();
        
        $this->googleAdsClient = (new GoogleAdsClientBuilder())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken(getenv('GOOGLE_ADS_DEVELOPER_TOKEN'))
            ->withLoginCustomerId(getenv('GOOGLE_ADS_LOGIN_CUSTOMER_ID')) // Optional, for MCC accounts
            ->build();
    }
    
    public function getCampaignBudget($customerId, $budgetId)
    {
        $googleAdsCampaignBudgetServiceClient = $this->googleAdsClient->getCampaignBudgetServiceClient();
        $resourceName = ResourceNames::forCampaignBudget($customerId, $budgetId);
        
        return $googleAdsCampaignBudgetServiceClient->getCampaignBudget($resourceName);
    }
    
    public function getCampaignStats($customerId, $dateRange = 'LAST_30_DAYS')
    {
        // Get the GoogleAdsServiceClient
        $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
        
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
            WHERE 
                campaign.status != 'REMOVED'
                AND segments.date DURING $dateRange
            ORDER BY campaign.name
        ";
        
        // Execute the query
        $response = $googleAdsServiceClient->search(
            $customerId,
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
        
        return $campaigns;
    }
    
    public function updateCampaignStatus($customerId, $campaignId, $status)
    {
        $campaignOperation = new CampaignOperation();
        $campaign = new Campaign();
        $campaign->setResourceName(ResourceNames::forCampaign($customerId, $campaignId));
        $campaign->setStatus($status);
        $campaignOperation->setUpdate($campaign);
        $campaignOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaign));
        
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns($customerId, [$campaignOperation]);
        
        return $response->getResults()[0];
    }
    
    public function updateCampaignBudget($customerId, $campaignId, $budgetMicros)
    {
        // First, get the campaign to find the budget ID
        $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
        $query = "SELECT campaign.campaign_budget FROM campaign WHERE campaign.id = $campaignId";
        $response = $googleAdsServiceClient->search($customerId, $query);
        
        $campaign = $response->iterateAllElements()->current()->getCampaign();
        $budgetResourceName = $campaign->getCampaignBudget();
        $budgetId = substr($budgetResourceName, strrpos($budgetResourceName, '/') + 1);
        
        // Now update the budget
        $campaignBudget = new CampaignBudget();
        $campaignBudget->setResourceName(ResourceNames::forCampaignBudget($customerId, $budgetId));
        $campaignBudget->setAmountMicros($budgetMicros);
        
        $campaignBudgetOperation = new CampaignBudgetOperation();
        $campaignBudgetOperation->setUpdate($campaignBudget);
        $campaignBudgetOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaignBudget));
        
        $campaignBudgetServiceClient = $this->googleAdsClient->getCampaignBudgetServiceClient();
        $response = $campaignBudgetServiceClient->mutateCampaignBudgets($customerId, [$campaignBudgetOperation]);
        
        return $response->getResults()[0];
    }
}