<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;
use App\Controllers\Api\SdkValidasi;
// use App\System\NexaModel;
/**
 * Test Controller untuk API endpoints
 */
class SdkController extends NexaController
{
    
    /**
     * Get memory usage statistics
     */
    private function store(): array
    {
        $model = $this->refModels('Office');
        return $model;
    }
    /**
     * Test endpoint
     */
  public function index(array $data = [], $params = []): array
{
    if ($data) {
        // $app = $params['auth']['data']['applications'];
        return self::red($data, $params);
    } else {
        return $this->message($params);
    }
}

       /**
     * PUT /api/info/health
     * Health check endpoint
     */
   public function app(array $data = [], $params = []): array
    {

        $key = $this->Crypto->CreateKey('NexaApp');
        $app = $params['data']['applications'];
        return [
            'status' => 'success',
            'token' => $this->Crypto->encode($app, $key)
        ];  

    }
    public function buckets(array $data = [], $params = []): array
    {
        $authHeader = $this->getHeader('Authorization', '');
        $key = $this->Crypto->CreateKey('NexaBuckets');
        $result = $this->Storage('nexa_office')
           ->select(['buckets'])
           ->where('authorization', $authHeader)
           ->first();




        return [
            'status' => 'success',
            'token' => $this->Crypto->encode($result['buckets'], $key)
        ];  

    }


    public function red(array $data = [], $params = []): array
    {

        if ($params['status']) {
        // Ambil aplikasi dari params terlebih dahulu
        $app = $params['data']['applications'];
        
        // Cek jika $data memiliki limit dan offset, maka update nilai tersebut di $app
        // Semua variabel lain di $app tetap dipertahankan, hanya limit dan offset yang diupdate
        if (isset($data['limit'])) {
            $app['limit'] = $data['limit'];
        }
        if (isset($data['offset'])) {
            $app['offset'] = $data['offset'];
        }
        // Ambil nilai offset untuk perhitungan nomor urut (dari $data jika ada, atau dari $app)
        $offset = isset($data['offset']) ? $data['offset'] : (isset($app['offset']) ? $app['offset'] : 0);
        // Ambil nilai limit untuk perhitungan pagination (dari $data jika ada, atau dari $app)
        $limit = isset($data['limit']) ? $data['limit'] : (isset($app['limit']) ? $app['limit'] : 10);
        
        $result = $this->refModels('Office')->executeOperation($app);
        
         // Tambahkan field "no" di urutan pertama pada setiap item response
         if (isset($result['response']) && is_array($result['response'])) {
             foreach ($result['response'] as $index => &$item) {
                 // Hitung nomor urut: offset + index + 1
                 $no = $offset + $index + 1;
                 // Buat array baru dengan "no" di urutan pertama
                 $newItem = ['no' => $no];
                 // Gabungkan dengan item yang sudah ada
                 $newItem = array_merge($newItem, $item);
                 $result['response'][$index] = $newItem;
             }
             unset($item); // Hapus reference
         }
        
         // Pastikan totalCount ada - jika tidak ada, gunakan count dari response
         if (!isset($result['totalCount']) || $result['totalCount'] === 0) {
             $responseCount = isset($result['response']) && is_array($result['response']) ? count($result['response']) : 0;
             // Jika ada data di response, set totalCount minimal sama dengan count
             // Jika tidak ada data, biarkan 0
             $result['totalCount'] = $responseCount;
         }
        
         // Pastikan count ada (jumlah data di halaman ini)
         if (!isset($result['count'])) {
             $result['count'] = isset($result['response']) && is_array($result['response']) ? count($result['response']) : 0;
         }
        
         // Tambahkan informasi pagination: totalPages dan currentPage
         if (isset($result['totalCount']) && $result['totalCount'] > 0 && $limit > 0) {
             $result['totalPages'] = (int)ceil($result['totalCount'] / $limit);
             $result['currentPage'] = (int)(floor($offset / $limit) + 1);
         } else {
             // Jika tidak ada data, set totalPages = 0 dan currentPage = 1
             $result['totalPages'] = 0;
             $result['currentPage'] = 1;
         }
        
        return $result;

        } else {
          return $this->message($params);
        }
        
   
    }
    
   /**
     * GET /api/info/health
     * Health check endpoint
     */
    public function updated(array $data = [], $params = []): array
    {
        if ($data) {
            // Ambil fieldConfig dari $data['update'] jika ada, kemudian hapus dari update
            $fieldConfig = $data['update']['fieldConfig'] ?? null;
            if (isset($data['update']['fieldConfig'])) {
                unset($data['update']['fieldConfig']); // Hapus fieldConfig dari update
            }
            
            $result = $this->refModels('Office')->setRetUpdate(
                $data['key'],
                $data['className'],
                $data['update'],
                $data['recordId'],
                $fieldConfig // Pass fieldConfig ke setRetUpdateReact
            );
              return [
                    'status' => 'success',
                    'message' => 'Info updated successfully',
                    'data' => $result
                ];
        } else {
            return $this->message($params);
        }
    }
    public function approval($data= []): array
    {
      try {
        // Validasi data required

        // Cari data approval yang sudah ada berdasarkan record_id dan userid
        // Menggunakan setRetFindKey untuk mencari seperti di web version
        // Tapi karena perlu filter berdasarkan record_id DAN userid, kita perlu query manual
        $model = $this->refModels('Office');
        
        // Cari approval dengan record_id dan userid
        // Menggunakan Storage dengan nama class "Approval" (huruf besar)
        $cek = $this->Storage("approval")
           ->where('record_id', $data['record_id'])
           ->where('userid', $data['userid'])
           ->orderBy("id", "DESC")
           ->first();
           
        if (!empty($cek['id'])) {
          // Update existing approval
          // setRetUpdate(key, className, data, recordId, fieldConfig)
          $resul = $model->setRetUpdate(
            276136656376989, 
            "Approval", // Huruf besar seperti di web version
            $data,
            $cek['id'], // Pass ID untuk update
            null // fieldConfig tidak diperlukan untuk approval
          ); 
        } else {
          // Insert new approval
          $resul = $model->setRetInsert(
            276136656376989, 
            "Approval", // Huruf besar seperti di web version
            $data
          ); 
        }
        
        return [
          'status' => 'success',
          'message' => 'Approval processed successfully',
          'data' => $resul ?? []
        ];
      } catch (\Exception $e) {
        return [
          'status' => 'error',
          'message' => $e->getMessage(),
          'data' => $data,
          'trace' => $e->getTraceAsString()
        ];
      }
    }
    public function approvalData($data= []): array
    {
      try {
        // Cari approval dengan record_id dan userid
        // Menggunakan Storage dengan nama class "Approval" (huruf besar)
        $resul = $this->Storage("approval")
           ->where('record_id', $data['record_id'])
           ->orderBy("id", "DESC")
           ->get();
        return [
          'status' => 'success',
          'message' => 'Approval processed successfully',
          'data' => $resul ?? []
        ];
      } catch (\Exception $e) {
        return [
          'status' => 'error',
          'message' => $e->getMessage(),
          'data' => $data,
          'trace' => $e->getTraceAsString()
        ];
      }
    }


    public function flag($data= []): array
    {
        $result = $this->refModels('Office')->flag();
        return $result ?? [];
    }

    


    public function search($data= []): array
    {
        $result = $this->refModels('Office')->searchAt($data,$data['query']);
        return $result ?? [];
    }
    public function pagination($data = [], $params = []): array
    {
        // HEAD request - return same data as red method
        // Use $data if available, otherwise use $params
        // $requestData = !empty($data) ? $data : $params;
        
        return [
            'status' => 'success',
            'message' => 'Red API pagination endpoint working! Ok',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s'),
            'data' => [
                'data' => $data,
                'params' => $params,
                'version' => '1.0.0'
            ]
        ];
    }
    
    
    public function deleted(array $data = []): array
    {


               $result = $this->refModels('Office')->setRettDelete(
                $data['key'],
                $data['className'],
                $data['recordId']
            );

        return [
            'status' => 'success',
            'message' => 'Info deleted successfully',
            'data' =>$result
        ];
    }
    
    /**
     * GET /api/info/health
     * Health check endpoint
     */
    public function created($data = [],$params = []): array
    {
        // ✅ FIX: Untuk FormData, ambil data dari $_POST jika $data kosong atau tidak ada fieldConfig
        // getRequestData() mungkin tidak membaca FormData dengan benar untuk SdkController
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipartFormData = strpos($contentType, 'multipart/form-data') !== false;
        
        if ($isMultipartFormData && !empty($_POST)) {
            // ✅ FIX: Merge $_POST ke $data untuk FormData
            $data = array_merge($data, $_POST);
            error_log("✅ [SdkController] FormData detected, merged \$_POST to \$data. \$_POST keys: " . implode(', ', array_keys($_POST)));
        } else if (empty($data) && !empty($_POST)) {
            $data = $_POST;
            error_log("✅ [SdkController] Data taken from \$_POST");
        }
        
        // ✅ FIX: Log data yang diterima untuk debugging
        error_log("🔍 [SdkController] Data received: " . json_encode(array_keys($data)) . " | Has fieldConfig: " . (isset($data['fieldConfig']) ? 'yes' : 'no'));
        
        // ✅ FIX: Merge $_FILES ke $data sebelum validasi (untuk FormData uploads)
        if (!empty($_FILES)) {
            foreach ($_FILES as $fieldName => $fileData) {
                if (isset($fileData['tmp_name']) && is_uploaded_file($fileData['tmp_name'])) {
                    // ✅ FIX: Set file field di $data untuk validasi (skip validasi file karena sudah ada di $_FILES)
                    // Validasi akan skip file jika ada di $_FILES
                    $data[$fieldName] = [
                        'name' => $fileData['name'],
                        'size' => $fileData['size'],
                        'type' => $fileData['type'],
                        '_isFileUpload' => true
                    ];
                    error_log("✅ [SdkController] File merged from \$_FILES: {$fieldName} - {$fileData['name']} ({$fileData['size']} bytes)");
                }
            }
        }
        
        // Validate required fields menggunakan SdkValidasi
        // Ambil konfigurasi validasi dari $params['data']['validasi']
        $validasiConfig = $params['data']['validasi'] ?? [];
        
        // ✅ FIX: Ambil fieldConfig dari $data jika ada, kemudian hapus dari $data sebelum validasi
        $fieldConfig = $data['fieldConfig'] ?? null;
        if (isset($data['fieldConfig'])) {
            // ✅ FIX: Jika fieldConfig adalah string (JSON dari FormData), decode dulu
            if (is_string($fieldConfig)) {
                error_log("🔍 [SdkController] fieldConfig is string, attempting to decode. Length: " . strlen($fieldConfig));
                $decodedFieldConfig = json_decode($fieldConfig, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFieldConfig)) {
                    $fieldConfig = $decodedFieldConfig;
                    error_log("✅ [SdkController] fieldConfig successfully decoded to array");
                } else {
                    error_log("⚠️ [SdkController] Failed to decode fieldConfig JSON: " . json_last_error_msg() . " | Content preview: " . substr($fieldConfig, 0, 100));
                    $fieldConfig = null;
                }
            } else {
                error_log("🔍 [SdkController] fieldConfig type: " . gettype($fieldConfig) . (is_array($fieldConfig) ? " (array with " . count($fieldConfig) . " items)" : ""));
            }
            unset($data['fieldConfig']); // Hapus fieldConfig dari data sebelum validasi
        } else {
            error_log("⚠️ [SdkController] fieldConfig not found in \$data");
        }
        
        // ✅ FIX: Pastikan fieldConfig adalah array atau null sebelum dikirim ke setRetInsert
        if ($fieldConfig !== null && !is_array($fieldConfig)) {
            error_log("⚠️ [SdkController] fieldConfig is not array before setRetInsert, converting to null. Type: " . gettype($fieldConfig) . " | Value: " . (is_string($fieldConfig) ? substr($fieldConfig, 0, 100) : var_export($fieldConfig, true)));
            $fieldConfig = null;
        }
        
        // ✅ FIX: Log final fieldConfig sebelum dikirim ke setRetInsert
        if ($fieldConfig !== null) {
            error_log("✅ [SdkController] fieldConfig ready for setRetInsert: " . (is_array($fieldConfig) ? "array with " . count($fieldConfig) . " items" : gettype($fieldConfig)));
        } else {
            error_log("ℹ️ [SdkController] fieldConfig is null (will be passed as null to setRetInsert)");
        }
        
        // ✅ FIX: Remove 'files' field dari $data (tidak ada di database, hanya metadata dari getRequestData)
        unset($data['files']);
        
        // Inisialisasi class validasi
        $validator = new SdkValidasi("insert");
        // Validasi input data
        $validationResult = $validator->validate($data, $validasiConfig);
    
        // Jika validasi gagal, return hasil validasi dengan input yang sudah dimodifikasi
        if ($validationResult['failed']) {
            return [
                'status' => 'failed',
                'message' => 'Validasi gagal',
                'input' => $validationResult['input'], // Input yang sudah dimodifikasi (field gagal diganti dengan pesan error)
                // 'validasi' => $validasiConfig,
                // 'errors' => $validationResult['errors']
            ];

        }
       $resul=  $this->refModels('Office')->setRetInsert(
            $params['config']['appid'], 
            $params['config']['endpoind'], 
            $data, 
            $fieldConfig); // Pass fieldConfig ke setRetInsertReact
        
        // Hapus field id dari hasil karena mode insert (id tidak perlu ditampilkan)
        if (is_array($resul) && isset($resul['id'])) {
            unset($resul['id']);
        }
        
        // Jika validasi berhasil, process the data
        return [
            'status' => 'success',
            'message' => 'Data berhasil divalidasi',
            'input' => $resul,
            // 'validasi' => $validasiConfig
        ];
    }
    /**
     * GET /api/info/status
     * Detailed system status
     */
    public function status(): array
    {
        return [
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
                'request_time' => date('Y-m-d H:i:s'),
                'timezone' => date_default_timezone_get(),
            ],
            'application' => [
                'environment' => getenv('APP_ENV') ?: 'production',
                'debug_mode' => (bool)getenv('APP_DEBUG'),
                'version' => '1.0.0'
            ]
        ];
    }
  public function package($data = [],$params = []): array
    {
        return [
            'server' => [
                'data' =>$data,
                'params' => $params,
            ]
        ];
    }
    /**
     * GET /api/info/version
     * Get API version information
     */
    public function version(): array
    {
        return [
            'api_version' => '1.0.0',
            'framework_version' => '1.0.0',
            'release_date' => '2024-03-20',
            'supported_versions' => ['1.0.0'],
            'deprecated_versions' => []
        ];
    }

    /**
     * Get memory usage statistics
     */
    private function message(array $data): array
    {
        return [
            'status' => 'error',
            'message' => 'Silakan periksa authorization dan endpoint yang digunakan',
            'data' => $data
        ];
    }
} 