<?php 

namespace App\Controllers;

use App\Models\GoogleAccountModel;
use CodeIgniter\Controller;
use Google\Client as GoogleClient;

class GoogleAuth extends Controller
{
    private $client;
    
    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(getenv('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri(base_url('google/callback'));
        $this->client->addScope('https://www.googleapis.com/auth/adwords');
        $this->client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $this->client->addScope('https://www.googleapis.com/auth/userinfo.profile');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        $this->googleAccountModel = new GoogleAccountModel();
    }
    
    public function connect()
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $authUrl = $this->client->createAuthUrl();
        return redirect()->to($authUrl);
    }
    
    public function callback()
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $userId = $session->get('user_id');
        
        if ($this->request->getVar('code')) {
            $token = $this->client->fetchAccessTokenWithAuthCode($this->request->getVar('code'));
            $this->client->setAccessToken($token);
            
            // Get user info
            $google_oauth = new \Google_Service_Oauth2($this->client);
            $google_account_info = $google_oauth->userinfo->get();
            $google_account_id = $google_account_info->id;
            
            // Check if user exists
            $existingAccount = $this->googleAccountModel->getAccountByGoogleId($userId, $google_account_id);
            
            if ($existingAccount) {
                // Update existing account
                $this->googleAccountModel->update($existingAccount['id'], [
                    'access_token' => json_encode($token),
                    'refresh_token' => $token['refresh_token'] ?? $existingAccount['refresh_token'],
                    'token_expiry' => date('Y-m-d H:i:s', time() + $token['expires_in']),
                    'name' => $google_account_info->name,
                    'email' => $google_account_info->email
                ]);
            } else {
                // Create new account
                $this->googleAccountModel->save([
                    'user_id' => $userId,
                    'google_account_id' => $google_account_id,
                    'access_token' => json_encode($token),
                    'refresh_token' => $token['refresh_token'] ?? '',
                    'token_expiry' => date('Y-m-d H:i:s', time() + $token['expires_in']),
                    'name' => $google_account_info->name,
                    'email' => $google_account_info->email
                ]);
            }
            
            return redirect()->to('/google/ads/accounts')->with('success', 'Google account connected successfully');
        }
        
        return redirect()->to('/dashboard')->with('error', 'Failed to connect Google account');
    }
}