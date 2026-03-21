<?php
namespace App\Controllers\Api;

use App\System\NexaController;

/**
 * Google Authentication Controller
 * Handles Google Sign-In for signup and signin
 */
class GoogleAuthController extends NexaController {
    
    private $googleClientId = '439618760894-3230nsh6lbsvo9sier0kdfnunt5743id.apps.googleusercontent.com';
    
    /**
     * Test endpoint - untuk debugging
     */
    public function test() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Google Auth API is working!',
            'client_id' => $this->googleClientId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    /**
     * Handle Google Sign-Up
     */
    public function signup() {
        // IMPORTANT: Set headers first to prevent any output before JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Clean output buffer to prevent HTML comments
        if (ob_get_level()) {
            ob_clean();
        }
        
        if (!$this->isPost()) {
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            exit; // Exit immediately after JSON response
        }
        
        // Get JSON input
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['credential'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Credential tidak ditemukan'
            ]);
            exit;
        }
        
        // Verify and decode Google JWT token
        $googleUser = $this->verifyGoogleToken($data['credential']);
        
        if (!$googleUser) {
            echo json_encode([
                'success' => false,
                'message' => 'Token Google tidak valid'
            ]);
            exit;
        }
        
        // Check if user already exists
        $userModel = $this->refModels('Oauth');
        $existingUser = $userModel->Storage('user')
            ->select(['*'])
            ->where('email', $googleUser['email'])
            ->first();
        
        if ($existingUser) {
            // User already exists, log them in instead
            $userEntity = $this->slugify($existingUser['nama']);
            
            $this->setUser([
                'user_id' => $existingUser['id'],
                'userid' => $existingUser['userid'] ?? $existingUser['id'],
                'user_name' => $userEntity,
                'user_real_name' => $existingUser['nama'],
                'email' => $existingUser['email'],
                'avatar' => $this->url($existingUser['avatar'] ?? 'images/pria.png'),
                'status' => $existingUser['status'] ?? 'user',
                'role' => $existingUser['status'] ?? 'user',
                'login_time' => time(),
                'last_activity' => time()
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Login berhasil dengan Google!',
                'redirect' => $this->url($userEntity),
                'user' => [
                    'name' => $existingUser['nama'],
                    'email' => $existingUser['email']
                ]
            ]);
            exit;
        }
        
        // Create new user
        // Generate secure random password for Google users
        // They won't use this password (login via Google), but it's required by DB
        $randomPassword = password_hash(uniqid('google_', true), PASSWORD_DEFAULT);
        
        // Clean name: remove quotes and extra spaces
        $cleanedName = $this->cleanGoogleName($googleUser['name']);
        
        $insertData = [
            'nama' => $cleanedName,
            'email' => $googleUser['email'],
            'password' => $randomPassword, // Secure bcrypt hash (60 chars)
            'status' => 'user',
            'role' => 'user',
            'row' => '1',
            'gender' => 'male', // Default
            'avatar' => '/assets/drive/avatar/pria.png'
        ];
        
        try {
            $result = $userModel->Storage('user')->insert($insertData);
            
            if ($result) {
                // Get the newly created user
                $newUser = $userModel->Storage('user')
                    ->select(['*'])
                    ->where('email', $googleUser['email'])
                    ->first();
                
                if ($newUser) {
                    // Auto login the user
                    $userEntity = $this->slugify($newUser['nama']);
                    
                    $this->setUser([
                        'user_id' => $newUser['id'],
                        'userid' => $newUser['userid'] ?? $newUser['id'],
                        'user_name' => $userEntity,
                        'user_real_name' => $newUser['nama'],
                        'email' => $newUser['email'],
                        'avatar' => $this->url($newUser['avatar'] ?? 'images/pria.png'),
                        'status' => $newUser['status'] ?? 'user',
                        'role' => $newUser['role'] ?? 'user',
                        'login_time' => time(),
                        'last_activity' => time()
                    ]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Pendaftaran berhasil dengan Google!',
                    'redirect' => $this->url($userEntity ?? '/'),
                    'user' => [
                        'name' => $cleanedName,
                        'email' => $googleUser['email']
                    ]
                ]);
                exit;
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menyimpan data user'
                ]);
                exit;
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Handle Google Sign-In
     */
    public function signin() {
        // IMPORTANT: Set headers first to prevent any output before JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Clean output buffer to prevent HTML comments
        if (ob_get_level()) {
            ob_clean();
        }
        
        if (!$this->isPost()) {
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            exit;
        }
        
        // Get JSON input
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['credential'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Credential tidak ditemukan'
            ]);
            exit;
        }
        
        // Verify and decode Google JWT token
        $googleUser = $this->verifyGoogleToken($data['credential']);
        
        if (!$googleUser) {
            echo json_encode([
                'success' => false,
                'message' => 'Token Google tidak valid'
            ]);
            exit;
        }
        
        // Check if user exists
        $userModel = $this->refModels('Oauth');
        $user = $userModel->Storage('user')
            ->select(['*'])
            ->where('email', $googleUser['email'])
            ->first();
        
        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'Akun tidak ditemukan. Silakan daftar terlebih dahulu.'
            ]);
            exit;
        }
        
        // Login the user
        $userEntity = $this->slugify($user['nama']);
        
        $this->setUser([
            'user_id' => $user['id'],
            'userid' => $user['userid'] ?? $user['id'],
            'user_name' => $userEntity,
            'user_real_name' => $user['nama'],
            'email' => $user['email'],
            'avatar' => $this->url($user['avatar'] ?? 'images/pria.png'),
            'status' => $user['status'] ?? 'user',
            'role' => $user['role'] ?? 'user',
            'login_time' => time(),
            'last_activity' => time()
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil dengan Google!',
            'redirect' => $this->url($userEntity),
            'user' => [
                'name' => $user['nama'],
                'email' => $user['email']
            ]
        ]);
        exit;
    }
    
    /**
     * Verify Google ID Token
     * 
     * @param string $token Google ID Token
     * @return array|false User data or false on failure
     */
    private function verifyGoogleToken($token) {
        try {
            // Decode JWT without verification (for development)
            // In production, you should verify the signature properly
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            
            if (!$payload) {
                return false;
            }
            
            // Verify issuer
            if (!isset($payload['iss']) || 
                ($payload['iss'] !== 'https://accounts.google.com' && 
                 $payload['iss'] !== 'accounts.google.com')) {
                return false;
            }
            
            // Verify audience (client ID)
            if (!isset($payload['aud']) || $payload['aud'] !== $this->googleClientId) {
                return false;
            }
            
            // Verify expiration
            if (!isset($payload['exp']) || $payload['exp'] < time()) {
                return false;
            }
            
            // Extract user data
            return [
                'email' => $payload['email'] ?? null,
                'name' => $payload['name'] ?? 'Google User',
                'picture' => $payload['picture'] ?? null,
                'email_verified' => $payload['email_verified'] ?? false,
                'sub' => $payload['sub'] ?? null // Google User ID
            ];
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Base64 URL Decode
     */
    private function base64UrlDecode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
    
    /**
     * Clean Google Name
     * Remove quotes and normalize spaces
     * Example: Abdul Maskhur "Iyan R Saleh" Saleh -> Abdul Maskhur Iyan R Saleh Saleh
     * 
     * @param string $name Original name from Google
     * @return string Cleaned name
     */
    private function cleanGoogleName($name) {
        if (empty($name)) {
            return 'Google User';
        }
        
        // Remove all types of quotes (single and double, straight and curly)
        $quotes = [
            '"',  // Straight double quote
            "'",  // Straight single quote
            "\xE2\x80\x9C", // Left double quotation mark (")
            "\xE2\x80\x9D", // Right double quotation mark (")
            "\xE2\x80\x98", // Left single quotation mark (')
            "\xE2\x80\x99"  // Right single quotation mark (')
        ];
        $name = str_replace($quotes, '', $name);
        
        // Normalize multiple spaces to single space
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Trim leading and trailing spaces
        $name = trim($name);
        
        return $name;
    }
}
