<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * Test Controller untuk API endpoints
 */
class SettingController extends NexaController
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
     * Avatar upload endpoint
     */
    public function avatar($data = [], $params = []): array
    {
        try {
            // Debug: Log semua data yang diterima
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? 'UNKNOWN';
            
            // Untuk multipart/form-data, baca langsung dari $_POST dan $_FILES
            // Validasi ID user harus ada - cek dari berbagai sumber
            $userId = $_POST['id'] ?? $data['id'] ?? null;
        
        // Jika masih null, coba baca dari getRequestData
        if (!$userId) {
            $requestData = $this->getRequestData();
            // Jika data adalah array dengan _parts (FormData dari React Native)
            if (isset($requestData['_parts']) && is_array($requestData['_parts'])) {
                foreach ($requestData['_parts'] as $part) {
                    if (is_array($part) && count($part) >= 2) {
                        $key = $part[0];
                        $value = $part[1];
                        if ($key === 'id') {
                            $userId = $value;
                            break;
                        }
                    }
                }
            } else {
                $userId = $requestData['id'] ?? null;
            }
        }
        
        // Validasi ID user harus ada dan bukan "userSession"
        if (!$userId || $userId === "userSession") {
            return [
                'status' => 'error',
                'message' => 'ID user tidak ditemukan atau tidak valid. Silakan login ulang.',
                'timestamp' => time(),
                'error_code' => 'USER_ID_REQUIRED',
                'debug' => [
                    'post' => $_POST,
                    'data' => $data,
                    'userId_received' => $userId
                ]
            ];
        }

        // Cek apakah user ada di database
        $existingUser = $this->Storage('user')
            ->where("id", $userId)
            ->first();

        if (!$existingUser) {
            return [
                'status' => 'error',
                'message' => 'User tidak ditemukan di database.',
                'timestamp' => time(),
                'error_code' => 'USER_NOT_FOUND'
            ];
        }

        // Handle file upload
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            // Debug: log error untuk troubleshooting
            $fileError = $_FILES['avatar']['error'] ?? 'FILE_NOT_SET';
            return [
                'status' => 'error',
                'message' => 'File avatar tidak ditemukan atau terjadi error saat upload. Error code: ' . $fileError,
                'timestamp' => time(),
                'error_code' => 'FILE_UPLOAD_ERROR',
                'debug' => [
                    'request_method' => $requestMethod,
                    'content_type' => $contentType,
                    'files' => $_FILES,
                    'post' => $_POST,
                    'get' => $_GET,
                    'userId' => $userId,
                    'data_param' => $data,
                    'params_param' => $params
                ]
            ];
        }

        $uploadedFile = $_FILES['avatar'];
        
        // Validasi file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $fileType = mime_content_type($uploadedFile['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            return [
                'status' => 'error',
                'message' => 'Format file tidak didukung. Gunakan JPG atau PNG.',
                'timestamp' => time(),
                'error_code' => 'INVALID_FILE_TYPE'
            ];
        }

        // Validasi file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($uploadedFile['size'] > $maxSize) {
            return [
                'status' => 'error',
                'message' => 'Ukuran file terlalu besar. Maksimal 5MB.',
                'timestamp' => time(),
                'error_code' => 'FILE_TOO_LARGE'
            ];
        }

        // Tentukan ekstensi file
        $extension = 'jpg';
        if ($fileType === 'image/png') {
            $extension = 'png';
        } elseif ($fileType === 'image/jpeg' || $fileType === 'image/jpg') {
            $extension = 'jpeg';
        }

        // Buat struktur folder berdasarkan tahun dan bulan (format: YYYY/MM)
        $year = date('Y');
        $month = date('m');
        
        // Generate nama file unik: avatar_{userId}_{timestamp}_{random}.{extension}
        $timestamp = time();
        $random = substr(md5(uniqid('', true)), 0, 8);
        $fileName = 'avatar_' . $userId . '_' . $timestamp . '_' . $random . '.' . $extension;
        
        // Path folder upload dengan struktur tahun/bulan
        $uploadDir = dirname(__DIR__, 2) . '/assets/drive/avatar/' . $year . '/' . $month . '/';
        
        // Pastikan folder ada (dengan struktur tahun/bulan)
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal membuat folder upload.',
                    'timestamp' => time(),
                    'error_code' => 'FOLDER_CREATE_FAILED'
                ];
            }
        }

        // Path file lengkap
        $filePath = $uploadDir . $fileName;
        
        // Debug: Log info file upload
        $uploadInfo = [
            'tmp_name' => $uploadedFile['tmp_name'],
            'tmp_exists' => file_exists($uploadedFile['tmp_name']),
            'tmp_size' => filesize($uploadedFile['tmp_name']),
            'target_path' => $filePath,
            'upload_dir' => $uploadDir,
            'dir_exists' => is_dir($uploadDir),
            'dir_writable' => is_writable($uploadDir),
            'file_name' => $fileName
        ];
        
        // Tidak perlu hapus file lama, biarkan tersimpan untuk history
        // File baru akan tersimpan dengan nama unik di folder tahun/bulan

        // Pindahkan file dari temp ke folder upload dulu
        $tempFilePath = $uploadDir . 'temp_' . $fileName;
        $moveResult = move_uploaded_file($uploadedFile['tmp_name'], $tempFilePath);
        
        if (!$moveResult) {
            $errorDetails = array_merge($uploadInfo, [
                'error' => error_get_last(),
                'move_result' => false
            ]);
            return [
                'status' => 'error',
                'message' => 'Gagal menyimpan file.',
                'timestamp' => time(),
                'error_code' => 'FILE_SAVE_FAILED',
                'debug' => $errorDetails
            ];
        }
        
        // Resize gambar menjadi 150x150
        $resizeResult = $this->resizeImage($tempFilePath, $filePath, 150, 150);
        
        // Hapus file temp setelah resize
        if (file_exists($tempFilePath)) {
            @unlink($tempFilePath);
        }
        
        if (!$resizeResult) {
            return [
                'status' => 'error',
                'message' => 'Gagal resize gambar.',
                'timestamp' => time(),
                'error_code' => 'IMAGE_RESIZE_FAILED'
            ];
        }
        
        // Verifikasi file benar-benar tersimpan setelah resize
        if (!file_exists($filePath)) {
            return [
                'status' => 'error',
                'message' => 'File tidak ditemukan setelah resize.',
                'timestamp' => time(),
                'error_code' => 'FILE_NOT_FOUND_AFTER_RESIZE',
                'debug' => array_merge($uploadInfo, [
                    'move_result' => $moveResult,
                    'resize_result' => $resizeResult,
                    'file_exists_after' => file_exists($filePath)
                ])
            ];
        }
        
        // Verifikasi ukuran file setelah resize
        $finalFileSize = filesize($filePath);
        if ($finalFileSize === 0) {
            return [
                'status' => 'error',
                'message' => 'File tidak tersimpan dengan benar setelah resize.',
                'timestamp' => time(),
                'error_code' => 'FILE_SIZE_ZERO',
                'debug' => [
                    'original_size' => $uploadedFile['size'],
                    'final_size' => $finalFileSize,
                    'file_path' => $filePath
                ]
            ];
        }

        // Path untuk database: avatar/{year}/{month}/{fileName}
        $avatarPath = 'avatar/' . $year . '/' . $month . '/' . $fileName;

        // Verifikasi file masih ada sebelum update database
        if (!file_exists($filePath)) {
            return [
                'status' => 'error',
                'message' => 'File hilang sebelum update database.',
                'timestamp' => time(),
                'error_code' => 'FILE_MISSING_BEFORE_UPDATE',
                'debug' => [
                    'file_path' => $filePath,
                    'file_exists' => file_exists($filePath)
                ]
            ];
        }

        // Update database
        $updateResult = $this->Storage('user')
            ->where("id", $userId)
            ->update(['avatar' => $avatarPath]);

        if (!$updateResult) {
            // Jika update gagal, hapus file yang sudah di-upload
            @unlink($filePath);
            return [
                'status' => 'error',
                'message' => 'Gagal mengupdate database.',
                'timestamp' => time(),
                'error_code' => 'DATABASE_UPDATE_FAILED',
                'debug' => [
                    'user_id' => $userId,
                    'avatar_path' => $avatarPath
                ]
            ];
        }

        // Verifikasi file masih ada setelah update database
        if (!file_exists($filePath)) {
            return [
                'status' => 'error',
                'message' => 'File hilang setelah update database.',
                'timestamp' => time(),
                'error_code' => 'FILE_MISSING_AFTER_UPDATE',
                'debug' => [
                    'file_path' => $filePath,
                    'file_exists' => file_exists($filePath),
                    'update_result' => $updateResult
                ]
            ];
        }

        // Generate URL avatar
        $avatarUrl = $this->url('assets/drive/' . $avatarPath);

        return [
            'status' => 'success',
            'message' => 'Avatar berhasil diupload.',
            'timestamp' => time(),
            'data' => [
                'fileUrl' => $avatarUrl,
                'fileName' => $fileName,
                'avatarPath' => $avatarPath,
                'userId' => $userId,
                'fileSize' => filesize($filePath),
                'fileExists' => file_exists($filePath)
            ]
        ];
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses upload: ' . $e->getMessage(),
                'timestamp' => time(),
                'error_code' => 'UNEXPECTED_ERROR',
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }
    
    /**
     * Resize image to specified dimensions
     * @param string $sourcePath Path to source image
     * @param string $destinationPath Path to save resized image
     * @param int $width Target width
     * @param int $height Target height
     * @return bool Success status
     */
    private function resizeImage($sourcePath, $destinationPath, $width = 150, $height = 150): bool
    {
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Create image resource based on mime type
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($sourcePath);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create new image with target dimensions
        $destinationImage = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($destinationImage, false);
            imagesavealpha($destinationImage, true);
            $transparent = imagecolorallocatealpha($destinationImage, 255, 255, 255, 127);
            imagefilledrectangle($destinationImage, 0, 0, $width, $height, $transparent);
        }
        
        // Calculate scaling to fit within 150x150 while maintaining aspect ratio
        $scale = min($width / $originalWidth, $height / $originalHeight);
        $newWidth = (int)($originalWidth * $scale);
        $newHeight = (int)($originalHeight * $scale);
        
        // Calculate position to center the image
        $x = (int)(($width - $newWidth) / 2);
        $y = (int)(($height - $newHeight) / 2);
        
        // Fill background with white for non-transparent images
        if ($mimeType !== 'image/png') {
            $white = imagecolorallocate($destinationImage, 255, 255, 255);
            imagefill($destinationImage, 0, 0, $white);
        }
        
        // Enable better quality resampling
        // Use imagecopyresampled with better interpolation
        // For better quality, we can create an intermediate larger image first if needed
        if ($originalWidth > $width * 2 || $originalHeight > $height * 2) {
            // For large images, resize in steps for better quality
            $intermediateWidth = $newWidth * 2;
            $intermediateHeight = $newHeight * 2;
            $intermediateImage = imagecreatetruecolor($intermediateWidth, $intermediateHeight);
            
            if ($mimeType === 'image/png') {
                imagealphablending($intermediateImage, false);
                imagesavealpha($intermediateImage, true);
                $transparent = imagecolorallocatealpha($intermediateImage, 255, 255, 255, 127);
                imagefilledrectangle($intermediateImage, 0, 0, $intermediateWidth, $intermediateHeight, $transparent);
            }
            
            // First resize to intermediate size
            imagecopyresampled(
                $intermediateImage,
                $sourceImage,
                0, 0, 0, 0,
                $intermediateWidth, $intermediateHeight,
                $originalWidth, $originalHeight
            );
            
            // Then resize to final size
            imagecopyresampled(
                $destinationImage,
                $intermediateImage,
                $x, $y, 0, 0,
                $newWidth, $newHeight,
                $intermediateWidth, $intermediateHeight
            );
            
            imagedestroy($intermediateImage);
        } else {
            // Direct resize for smaller images
            imagecopyresampled(
                $destinationImage,
                $sourceImage,
                $x, $y, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );
        }
        
        // Save resized image with higher quality
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                // Use higher quality (90) for better sharpness
                $result = imagejpeg($destinationImage, $destinationPath, 90);
                break;
            case 'image/png':
                // Use compression level 3 (higher quality, less compression)
                $result = imagepng($destinationImage, $destinationPath, 3);
                break;
            case 'image/gif':
                $result = imagegif($destinationImage, $destinationPath);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    // Use higher quality for webp
                    $result = imagewebp($destinationImage, $destinationPath, 90);
                }
                break;
        }
        
        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($destinationImage);
        
        return $result;
    }
} 