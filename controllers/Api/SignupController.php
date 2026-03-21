<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * Test Controller untuk API endpoints
 */
class SignupController extends NexaController
{
    /**
     * Test endpoint
     */
    public function index(): array
    {
        return [
            'status' => 'success',
            'message' => 'Test API endpoint working!',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s'),
            'data' => [
                'test' => true,
                'version' => '1.0.0'
            ]
        ];
    }
    
    /**
     * Hello endpoint
     */
    public function hello(): array
    {
        return [
            'status' => 'success',
            'message' => 'Hello from API!',
            'timestamp' => time(),
            'greeting' => 'Hello World!'
        ];
    }
    
    /**
     * Error test endpoint
     */
    public function error(): array
    {
        return [
            'status' => 'error',
            'message' => 'This is a test error response',
            'error_code' => 'TEST_ERROR',
            'timestamp' => time()
        ];
    }
    
    /**
     * Created endpoint for POST requests
     */
    public function created($data = [], $params = []): array
    {
        // Validasi email sudah digunakan
        $existingEmail = $this->Storage('user')
            ->where("email", $data['email'])
            ->first();

        if ($existingEmail) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'message' => 'Email sudah terdaftar. Silakan gunakan email lain.',
                'timestamp' => time(),
                'error_code' => 'EMAIL_EXISTS'
            ];
        }

        // Validasi nomor HP sudah digunakan
        $existingPhone = $this->Storage('user')
            ->where("telepon", $data['phone'])
            ->first();

        if ($existingPhone) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'message' => 'Nomor HP sudah terdaftar. Silakan gunakan nomor HP lain.',
                'timestamp' => time(),
                'error_code' => 'PHONE_EXISTS'
            ];
        }

        // Mapping data untuk sesuai dengan struktur tabel
        // Kolom di database: telepon (bukan phone)
        $insertData = [
            'nama' => $data['nama'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => $data['password'] ?? '',
            'telepon' => $data['phone'] ?? '', // Map phone -> telepon
            'gender' => $data['gender'] ?? '',
            'status' => 'active', // Set default status
            'row' => '1' // Set default row value
        ];

        // Jika email dan phone belum digunakan, lanjutkan penyimpanan
        $insert = $this->Storage('user')->insert($insertData);

        return [
            'status' => 'success',
            'title' => 'Success',
            'message' => 'Pendaftaran berhasil. Silakan login untuk melanjutkan.',
            'timestamp' => time(),
            'data' => $insertData,
            'params' => $params
        ];
    }
    
    /**
     * Updated endpoint for PUT/PATCH requests
     */
    public function updated($data = [], $params = []): array
    {
        // Validasi ID user harus ada
        $userId = $data['id'] ?? null;
        if (!$userId) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'message' => 'ID user tidak ditemukan. Silakan login ulang.',
                'timestamp' => time(),
                'error_code' => 'USER_ID_REQUIRED'
            ];
        }

        // Cek apakah user ada di database
        $existingUser = $this->Storage('user')
            ->where("id", $userId)
            ->first();

        if (!$existingUser) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'message' => 'User tidak ditemukan di database.',
                'timestamp' => time(),
                'error_code' => 'USER_NOT_FOUND'
            ];
        }

        // Validasi email tidak duplikat (kecuali untuk user yang sama)
        if (isset($data['email']) && !empty($data['email'])) {
            // Cek email yang sama dengan user lain (bukan user saat ini)
            $emailCheck = $this->Storage('user')
                ->where("email", $data['email'])
                ->first();

            // Jika email ditemukan dan bukan milik user saat ini
            if ($emailCheck && $emailCheck['id'] != $userId) {
                return [
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'Email sudah digunakan oleh user lain. Silakan gunakan email lain.',
                    'timestamp' => time(),
                    'error_code' => 'EMAIL_EXISTS'
                ];
            }
        }

        // Validasi nomor HP tidak duplikat (kecuali untuk user yang sama)
        if (isset($data['phone']) && !empty($data['phone'])) {
            // Cek nomor HP yang sama dengan user lain (bukan user saat ini)
            $phoneCheck = $this->Storage('user')
                ->where("telepon", $data['phone'])
                ->first();

            // Jika nomor HP ditemukan dan bukan milik user saat ini
            if ($phoneCheck && $phoneCheck['id'] != $userId) {
                return [
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'Nomor HP sudah digunakan oleh user lain. Silakan gunakan nomor HP lain.',
                    'timestamp' => time(),
                    'error_code' => 'PHONE_EXISTS'
                ];
            }
        }

        // Mapping data untuk sesuai dengan struktur tabel
        $updateData = [];
        
        if (isset($data['fullName']) && !empty($data['fullName'])) {
            $updateData['nama'] = $data['fullName'];
        }
        
        if (isset($data['nik']) && !empty($data['nik'])) {
            $updateData['nik'] = $data['nik'];
        }
        
        if (isset($data['phone']) && !empty($data['phone'])) {
            $updateData['telepon'] = $data['phone']; // Map phone -> telepon
        }
        
        if (isset($data['email']) && !empty($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }
        
        if (isset($data['gender']) && !empty($data['gender'])) {
            $updateData['gender'] = $data['gender'];
        }
        
        if (isset($data['address']) && !empty($data['address'])) {
            $updateData['alamat'] = $data['address'];
        }

        // Update data user
        if (empty($updateData)) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'message' => 'Tidak ada data yang diupdate.',
                'timestamp' => time(),
                'error_code' => 'NO_DATA_TO_UPDATE'
            ];
        }

        // Update berdasarkan ID
        $updateResult = $this->Storage('user')
            ->where("id", $userId)
            ->update($updateData);

        if (!$updateResult) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'message' => 'Gagal mengupdate data. Silakan coba lagi.',
                'timestamp' => time(),
                'error_code' => 'UPDATE_FAILED'
            ];
        }

        return [
            'status' => 'success',
            'title' => 'Success',
            'message' => 'Data berhasil diupdate.',
            'timestamp' => time(),
            'data' => $updateData,
            'params' => $params
        ];
    }
    
    /**
     * Deleted endpoint for DELETE requests
     */
    public function deleted($data = [], $params = []): array
    {
        return [
            'status' => 'success',
            'message' => 'Test data deleted successfully',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
    }
} 