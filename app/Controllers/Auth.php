<?php 

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends Controller
{
    public function login()
    {
        if (strtolower($this->request->getMethod()) === 'post') {
            $email = $this->request->getPost('email');
            $password = $this->request->getPost('password');
            
            $userModel = new UserModel();
            $user = $userModel->getUserByEmail($email);
            
            if ($user && password_verify($password, $user['password'])) {
                $session = session();
                $session->set([
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'logged_in' => true
                ]);
                
                return redirect()->to('/dashboard');
            } else {
                return redirect()->back()->with('error', 'Invalid email or password');
            }
        }
        
        return view('auth/login');
    }
    
    public function register()
    {
        if (strtolower($this->request->getMethod()) === 'post') {
            $rules = [
                'username' => 'required|min_length[3]|max_length[20]',
                'email' => 'required|valid_email|is_unique[users.email]',
                'password' => 'required|min_length[6]',
                'confirm_password' => 'required|matches[password]',
            ];
            
            if ($this->validate($rules)) {
                $userModel = new UserModel();
                $userModel->save([
                    'username' => $this->request->getPost('username'),
                    'email' => $this->request->getPost('email'),
                    'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                ]);
                
                return redirect()->to('/login')->with('success', 'Registration successful');
            } else {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
        }
        
        return view('auth/register');
    }
    
    public function logout()
    {
        $session = session();
        $session->destroy();
        
        return redirect()->to('/login');
    }
}