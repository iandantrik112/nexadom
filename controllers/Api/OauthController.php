<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * RESTful Info API Controller
 * Handles system information endpoints
 */
class OauthController extends NexaController
{

    /**
     * GET /api/info
     * List all available information
     */
    public function index(): array
    {
        return [
            'status' => 'success',
            'data' => [
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    // 'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'uptime' => $this->getServerUptime(),
                    'memory' => $this->getMemoryUsage()
                ]
            ]
        ];
    }


    private function getFirebaseConfig(): array
    {
        return [
            'apiKey' => 'AIzaSyA0XUCGzsK7hhg8NmxisslthTeOU93dORA',
            'uniqueId' =>$this->session->getVisitorKey(),
            'authDomain' => 'nexaui-86863.firebaseapp.com',
            'databaseURL' => 'https://nexaui-86863-default-rtdb.firebaseio.com',
            'projectId' => 'nexaui-86863',
            'storageBucket' => 'nexaui-86863.firebasestorage.app',
            'messagingSenderId' => '1034885626532',
            'appId' => '1:1034885626532:web:64272a0e491f944dd04431',
            'Ai' => [
              'token' => "AIzaSyBwMr10A7hjBps0UtwH9c-6YAyAWlnebHM",
              'url' =>"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent",
            ],

        ];
    }



    public function config(array $data = []): array
    {
        $data=$this->getFirebaseConfig();
        $key = $this->Crypto->CreateKey('NexaQrV1');
        return [
            'status' => 'success',

            'token' => $this->Crypto->encode($data, $key)
        ];


      // $Chat = $this->refParams('Access/Chat');
      //  return $Chat->getFirebaseConfig();
    }

    public function qrsignin(array $data = []): array
    {
        $VisitorId = $this->session->getVisitorKey();
       
                  if (!is_array($data)) {
            return [
                'success' => false,
                'message' => 'Data format tidak valid'
            ];
        }
      
        // Check if required fields exist
        if (!isset($data['email']) || !isset($data['password'])) {
            return [
                'success' => false,
                'message' => 'Email dan password harus diisi'
            ];
        }
        
           $user = $this->refModels('Oauth');
           $status = $user->signin($data);
          
          if (!$status) {
              return [
                  'success' => false,
                  'message' => 'Email atau password salah',
                  'errors' => ['login' => 'Email atau password tidak valid']
              ];
          }
          // Set authenticated flag seperti di web controller
          $status['authenticated'] = true;
          $userEntity = $this->slugify($status['nama']);
          
          // Save user data to session seperti di web controller (baris 64-76)
          // Ini untuk API session, tapi web browser perlu login terpisah via /signin
          $this->setUser([
              'user_id' => $status['id'],
              'userid' => $status['userid'] ?? $status['id'], // Fallback to id if userid not available
              'user_name' => $userEntity,
              'user_real_name' => $status['nama'],
              'email' => $status['email'],
              'avatar' => $this->url(!empty($status['avatar']) ? 'assets/drive/'.$status['avatar'] : 'assets/drive/images/pria.png'),
              'status' => $status['status'] ?? 'user',
              'role' => $status['status'] ?? 'user',
              'login_time' => time(),
              'last_activity' => time()
          ]);
          
          // Return data untuk response (tanpa password untuk keamanan)
          $return = [
                  'success' => true,
                  'message' => 'Login berhasil',
                  'user_id' => $status['id'] ?? null,
                  'userid' => $status['userid'] ?? $status['id'] ?? null,
                  'user_name' => $userEntity,
                  'user_real_name' => $status['nama'] ?? '',
                  'instansi' => $status['instansi'] ?? '',

                  'email' => $status['email'] ?? '',
                   'avatar' => $this->url(!empty($status['avatar']) ? 'assets/drive/'.$status['avatar'] : 'assets/drive/images/pria.png'),
                  'status' => $status['status'] ?? 'user',
                  'role' => $status['status'] ?? 'user',
                  'login_time' => time(),
                  'last_activity' => time(),
                  // Tambahkan redirect URL untuk mobile app
                  'redirect' => $userEntity,
                  'dashboard_url' => $this->url('/' . $userEntity)
              ];

            // Data untuk Firebase (termasuk password untuk web login via app.js)
            // Password diperlukan untuk POST ke /signin web controller (seperti di app.js baris 26-27)
            $firebaseData = array_merge($return, [
                'password' => $data['password'] // Password hanya untuk Firebase, tidak dikembalikan ke mobile app
            ]);

            $firebase = $this->getFirebase();
            // Set konfigurasi Firebase
            $firebase->setConfig(
                'https://nexaui-86863-default-rtdb.firebaseio.com',
                'AIzaSyA0XUCGzsK7hhg8NmxisslthTeOU93dORA'
            );
            // Kirim data ke Firebase (termasuk email dan password untuk web login)
            // app.js akan membaca data ini dan POST ke /signin untuk set session web
            // Setelah itu app.js akan redirect ke dashboard (baris 39)
            $firebase->createKey('qrlogin', $firebaseData, $data['token']);
            
            // Return response tanpa password untuk mobile app
            return $return;
            
    }
    

    
    public function signin(array $data = [], $params = []): array
    {
        // Get data from request body if not provided
        if (empty($data)) {
            $data = $this->getRequestData();
        }
        
        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'Email dan password harus diisi',
                'errors' => [
                    'email' => empty($data['email']) ? 'Email wajib diisi' : '',
                    'password' => empty($data['password']) ? 'Password wajib diisi' : ''
                ]
            ];
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Format email tidak valid',
                'errors' => ['email' => 'Format email tidak valid']
            ];
        }
        
        // Validate password length
        if (strlen($data['password']) < 6) {
            return [
                'success' => false,
                'message' => 'Password minimal 6 karakter',
                'errors' => ['password' => 'Password minimal 6 karakter']
            ];
        }
        
        $user = $this->refModels('Oauth');
        $status = $user->signin($data);
        
        if (!$status) {
            return [
                'success' => false,
                'message' => 'Email atau password salah',
                'errors' => ['login' => 'Email atau password tidak valid']
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Login berhasil',
            'phone' => $status['telepon'] ?? null,
            'user_id' => $status['id'] ?? null,
            'address' => $status['alamat'] ?? null,
            'gender' => $status['gender'] ?? null,
            'userid' => $status['id'] ?? $status['userid'] ?? null,
            'user_name' => $status['nama'] ?? '',
            'user_real_name' => $status['nama'] ?? '',
            'instansi' => $status['instansi'] ?? '',
            'email' => $status['email'] ?? '',
            'password' => $status['password'] ?? '',
            'avatar' => $this->url(!empty($status['avatar']) ? 'assets/drive/'.$status['avatar'] : 'assets/drive/images/pria.png'),
            'status' => $status['status'] ?? 'user',
            'role' => $status['status'] ?? 'user',
            'login_time' => time(),
            'last_activity' => time()
        ];
    }
    
    /**
     * Created endpoint for POST requests (RESTful)
     * Redirects to signin method
     */
    public function created(array $data = [], $params = []): array
    {
        return $data;
    }


  
} 