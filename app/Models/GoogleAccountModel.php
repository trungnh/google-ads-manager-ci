<?php 

namespace App\Models;

use CodeIgniter\Model;

class GoogleAccountModel extends Model
{
    protected $table = 'google_accounts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'google_account_id', 'access_token', 'refresh_token', 'token_expiry', 'name', 'email', 'created_at', 'updated_at'];
    protected $useTimestamps = true;

    public function getAccountsByUserId($userId)
    {
        return $this->where('user_id', $userId)->findAll();
    }

    public function getAccountByGoogleId($userId, $googleId)
    {
        return $this->where('user_id', $userId)
                    ->where('google_account_id', $googleId)
                    ->first();
    }
}