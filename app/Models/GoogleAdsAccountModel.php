<?php 

namespace App\Models;

use CodeIgniter\Model;

class GoogleAdsAccountModel extends Model
{
    protected $table = 'google_ads_accounts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['google_account_id', 'customer_id', 'name', 'created_at', 'updated_at'];
    protected $useTimestamps = true;

    public function getAccountsByGoogleAccountId($googleAccountId)
    {
        return $this->where('google_account_id', $googleAccountId)->findAll();
    }
}