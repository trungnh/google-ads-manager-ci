<?php 

namespace App\Controllers;

use App\Models\GoogleAccountModel;
use App\Models\GoogleAdsAccountModel;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function __construct()
    {
        $this->googleAccountModel = new GoogleAccountModel();
        $this->googleAdsAccountModel = new GoogleAdsAccountModel();
    }
    
    public function index()
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $userId = $session->get('user_id');
        $googleAccounts = $this->googleAccountModel->getAccountsByUserId($userId);
        
        $data = [
            'title' => 'Dashboard',
            'google_accounts' => $googleAccounts
        ];
        
        return view('dashboard/index', $data);
    }
}