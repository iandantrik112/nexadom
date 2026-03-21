<?php
declare(strict_types=1);
namespace App\Controllers\Api;
/**
 * Class Validasi untuk validasi input data sebelum dikirim ke server
 */
class SdkValidasi
{
    private string $mode;
    
    /**
     * Constructor
     * 
     * @param string $mode Mode validasi: "insert" untuk skip validasi id, atau mode lainnya
     */
    public function __construct(string $mode = '')
    {
        $this->mode = $mode;
    }
    
    /**
     * Validasi input data berdasarkan konfigurasi validasi
     * 
     * @param array $input Data input yang akan divalidasi
     * @param array $validasiConfig Konfigurasi validasi
     * @return array Hasil validasi dengan status dan error messages
     */
    public function validate(array $input, array $validasiConfig): array
    {
        $errors = [];
        $failed = false;
        $modifiedInput = $input; // Copy input untuk dimodifikasi
        
        // Jika mode adalah "insert", filter validasi config untuk skip field "id"
        if ($this->mode === 'insert') {
            $validasiConfig = array_filter($validasiConfig, function($rule) {
                $fieldName = $rule['failed'] ?? $rule['label'] ?? '';
                return $fieldName !== 'id';
            });
            // Re-index array setelah filter
            $validasiConfig = array_values($validasiConfig);
        }
        
        // Loop melalui setiap rule validasi
        foreach ($validasiConfig as $rule) {
            $label = $rule['label'] ?? '';
            $fieldName = $rule['failed'] ?? $label;
            $validation = $rule['validation'] ?? 0;
            $type = $rule['type'] ?? 'text';
            $infoError = $rule['info_error'] ?? $label;
            
            // Skip validasi untuk field dengan type "approval"
            if ($type === 'approval') {
                continue;
            }
            
            // Cek apakah field ada di input
            $value = $input[$fieldName] ?? null;
            
            // Validasi berdasarkan type dan validation number
            $fieldError = $this->validateField($value, $fieldName, $type, $validation, $infoError);
            
            if ($fieldError !== null) {
                // Ganti nilai field yang gagal dengan pesan error
                $modifiedInput[$fieldName] = $fieldError;
                
                $errors[] = [
                    'field' => $fieldName,
                    'label' => $label,
                    'message' => $fieldError,
                    'info_error' => $infoError
                ];
                $failed = true;
            }
        }
        
        // Jika mode adalah "insert", hapus field "id" dari input yang akan di-return
        if ($this->mode === 'insert' && isset($modifiedInput['id'])) {
            unset($modifiedInput['id']);
        }
        
        return [
            'status' => $failed ? 'failed' : 'success',
            'failed' => $failed,
            'errors' => $errors,
            'input' => $modifiedInput, // Return input yang sudah dimodifikasi
            'validasi' => $validasiConfig
        ];
    }
    
    /**
     * Validasi field individual
     * 
     * @param mixed $value Nilai field
     * @param string $fieldName Nama field
     * @param string $type Tipe field
     * @param int $validation Aturan validasi (min length untuk text)
     * @param string $infoError Pesan error
     * @return string|null Pesan error atau null jika valid
     */
    private function validateField($value, string $fieldName, string $type, int $validation, string $infoError): ?string
    {
        // ✅ FIX: Untuk file type, cek $_FILES terlebih dahulu sebelum validasi empty
        if ($type === 'file') {
            // Jika file ada di $_FILES atau $value memiliki _isFileUpload, skip validasi empty
            if (!empty($_FILES[$fieldName]) || (is_array($value) && isset($value['_isFileUpload']))) {
                // File sudah di-upload, skip validasi empty
                return null; // File valid, tidak perlu validasi lebih lanjut
            }
        }
        
        // Cek jika field required tapi tidak ada
        if ($value === null || $value === '') {
            if ($validation > 0) {
                return "{$infoError} minimal {$validation} karakter";
            }
            return "{$infoError} tidak valid";
        }
        
        // Validasi berdasarkan type
        switch ($type) {
            case 'text':
            case 'password':
            case 'tel':
            case 'url':
            case 'search':
            case 'textarea':
            case 'hidden':
                // Validasi minimum length untuk text-based fields
                if ($validation > 0 && strlen((string)$value) < $validation) {
                    return "{$infoError} minimal {$validation} karakter";
                }
                break;
                
            case 'number':
            case 'currency':
            case 'range':
                // Validasi untuk numeric fields
                if (!is_numeric($value)) {
                    return "{$infoError} tidak valid";
                }
                if ($validation > 0 && (float)$value < $validation) {
                    return "{$infoError} minimal {$validation}";
                }
                break;
                
            case 'email':
                // Validasi format email
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$infoError} tidak valid";
                }
                // Validasi minimum length untuk email
                if ($validation > 0 && strlen((string)$value) < $validation) {
                    return "{$infoError} minimal {$validation} karakter";
                }
                break;
                
            case 'date':
                // Validasi format date (YYYY-MM-DD)
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) {
                    return "Format {$infoError} tidak valid (format: YYYY-MM-DD)";
                }
                break;
                
            case 'time':
                // Validasi format time (HH:MM atau HH:MM:SS)
                if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string)$value)) {
                    return "Format {$infoError} tidak valid (format: HH:MM)";
                }
                break;
                
            case 'select':
            case 'radio':
                // Validasi untuk select dan radio (harus ada nilai)
                if (empty($value)) {
                    return "{$infoError} harus dipilih";
                }
                break;
                
            case 'checkbox':
            case 'switch':
                // Validasi untuk checkbox dan switch (harus boolean atau 0/1)
                if (!is_bool($value) && $value !== 0 && $value !== 1 && $value !== '0' && $value !== '1') {
                    return "{$infoError} tidak valid";
                }
                break;
                
            case 'file':
                // ✅ FIX: Validasi untuk file - skip jika file ada di $_FILES atau sudah di-upload
                // File dari FormData akan ada di $_FILES, bukan di $value
                if (empty($value) && empty($_FILES[$fieldName])) {
                    return "{$infoError} harus diisi";
                }
                // ✅ FIX: Jika file ada di $_FILES atau $value memiliki _isFileUpload, skip validasi
                if (!empty($_FILES[$fieldName]) || (is_array($value) && isset($value['_isFileUpload']))) {
                    // File sudah di-upload, skip validasi
                    break;
                }
                break;
                
            case 'color':
                // Validasi format color (hex: #RRGGBB)
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', (string)$value)) {
                    return "Format {$infoError} tidak valid (format: #RRGGBB)";
                }
                break;
                
            case 'flag':
                // Validasi untuk flag (biasanya boolean atau string)
                if (empty($value)) {
                    return "{$infoError} harus diisi";
                }
                break;
                
            default:
                // Default validation untuk type lainnya (cek panjang karakter)
                if ($validation > 0 && strlen((string)$value) < $validation) {
                    return "{$infoError} minimal {$validation} karakter";
                }
        }
        
        return null;
    }
    
    /**
     * Validasi cepat - hanya return boolean
     * 
     * @param array $input Data input
     * @param array $validasiConfig Konfigurasi validasi
     * @return bool True jika valid, false jika tidak valid
     */
    public function isValid(array $input, array $validasiConfig): bool
    {
        $result = $this->validate($input, $validasiConfig);
        return !$result['failed'];
    }
} 