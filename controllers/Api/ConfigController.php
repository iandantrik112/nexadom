<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;
use Exception;

/**
 * RESTful Config Controller
 * Menangani konfigurasi aplikasi dan autentikasi
 */
class ConfigController extends NexaController 
{
    /**
     * Firebase Configuration
     */
    private function getFirebaseConfig(): array
    {
        return [
            'apiKey' => 'AIzaSyC2Jy55sVarbaH4C-MwXnWa3xbKbYaOc6E',
            'authDomain' => 'ngorey.firebaseapp.com',
            'databaseURL' => 'https://ngorey-default-rtdb.firebaseio.com',
            'projectId' => 'ngorey',
            'storageBucket' => 'ngorey.firebasestorage.app',
            'messagingSenderId' => '352105815853',
            'appId' => '1:352105815853:web:03934f4393a09072bbbda4'
        ];
    }

    /**
     * Limit Messages Configuration
     */
    private function getLimitMessages(): array
    {
        return [
            'cleanOldGrou'=>'30d',
            'limit' => 124,
            'chatGroup' => [
                'status' => true,
                'name' => 'Nexa',
                'avatar' => 'http://localhost/dev/assets/images/favicon.png'
            ],
            'voiceCalls' => true,
            'videoCalls' => true,
            'sound' => [
                'status' => true,
                'chat' => 'http://localhost/dev/assets/NexaUi/assets/notif.mp3'
            ],
            'messages' => 'Silakan perbarui akun Anda, atau hubungi call center tatiye.net jika ingin menambah kuota obrolan'
        ];
    }

    /**
     * GET /api/config
     * Mengambil konfigurasi aplikasi
     */
    public function getConfig(): array
    {
        return [
            'status' => 'success',
            'data' => [
                'firebase' => $this->getFirebaseConfig(),
                'limitMessages' => $this->getLimitMessages()
            ]
        ];
    }

    /**
     * POST /api/auth/login
     * Mengambil daftar semua user dengan pagination
     */
    public function index(): array
    {
        $username = $this->getPost('username');
        $password = $this->getPost('password');

        // Example validation (replace with your actual user validation)
        if ($username === 'admin' && $password === 'dantrik112') {
            // Create token payload
            $payload = [
                'user_id' => 1,
                'username' => $username,
                'role' => 'admin',
                'expired' => '1y',
                'metadata' => [
                    'login_method' => 'password',
                    'device' => 'web_browser',
                    'firebase_config' => $this->getFirebaseConfig(),
                    'chat_limits' => $this->getLimitMessages()
                ]
            ];
      
            // Generate token
            $token = $this->Authorization()->generateToken($payload);

            // Return success response with config
            return [
                'status' => 'success',
                'message' => 'Login successful',
                'token' => $token,
                'config' => [
                    'firebase' => $this->getFirebaseConfig(),
                    'limitMessages' => $this->getLimitMessages()
                ]
            ];
        } else {
            // Return error response
            return [
                'status' => 'error',
                'message' => 'Invalid credentials'
            ];
        }
    }

    /**
     * GET /api/config/firebase
     * Mengambil konfigurasi Firebase saja
     */
    public function firebase(): array
    {
        return [
            'status' => 'success',
            'data' => $this->getFirebaseConfig()
        ];
    }

    /**
     * GET /api/config/limits
     * Mengambil konfigurasi limit messages saja
     */
    public function limits(): array
    {
        return [
            'status' => 'success',
            'data' => $this->getLimitMessages()
        ];
    }

    /**
     * Protected endpoint example
     * Requires valid token
     */
    public function protected(): array 
    {
        // Validate token
        $tokenData = $this->Authorization()->validateToken();
        
        if (!$tokenData) {
            return [
                'status' => 'error',
                'message' => 'Unauthorized access'
            ];
        }

        // Token is valid, we can use the token data
        $data = [
            'sensitive_data' => 'This is protected information',
            'user_info' => [
                'id' => $tokenData['user_id'],
                'username' => $tokenData['username'],
                'role' => $tokenData['role']
            ],
            'config' => [
                'firebase' => $this->getFirebaseConfig(),
                'limitMessages' => $this->getLimitMessages()
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return [
            'status' => 'success',
            'data' => $data
        ];
    }
}