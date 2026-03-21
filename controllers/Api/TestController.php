<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * Test Controller untuk API endpoints
 */
class TestController extends NexaController
{






    public function index(): array
    {

  // // Get online status
  //           $online = $this->Storage('online_users_view')
  //              ->select(['online_status', 'nama', 'computed_status', 'last_seen'])
  //               ->where('id', 1)
  //               ->first();
            
  //           // Hitung minutes_ago secara akurat
  //           if ($online && isset($online['last_seen'])) {
  //               $lastSeen = strtotime($online['last_seen']);
  //               $now = time();
  //               $minutes = floor(($now - $lastSeen) / 60);
  //               $online['minutes_ago'] = $minutes;
                
  //               // Format waktu aktif
  //               if ($minutes < 1) {
  //                   $online['last_active'] = "Aktif sekarang";
  //               } elseif ($minutes < 60) {
  //                   $online['last_active'] = "Aktif {$minutes} menit lalu";
  //               } elseif ($minutes < 1440) { // kurang dari 24 jam
  //                   $hours = floor($minutes / 60);
  //                   $online['last_active'] = "Aktif {$hours} jam lalu";
  //               } else {
  //                   $days = floor($minutes / 1440);
  //                   $online['last_active'] = "Aktif {$days} hari lalu";
  //               }
  //           }
            
  //           $totalProducts = $this->Storage('products')
  //               ->where('userid', 1)
  //               ->count();
            
  //           // Get limited products
  //           $orderData=$this->Storage('products') 
  //               ->select('*')
  //               ->where('userid',1)
  //               ->orderBy("sold", "desc") 
  //               ->limit(20)
  //               ->get();
                
  //               if (!empty($orderData)) {
  //                   foreach ($orderData as &$product) {
  //                       try {
  //                           // Count thumbnails for this product
  //                           $thumbnailCount = $this->Storage('product_thumbnails')
  //                               ->where('product_id', $product['id'])
  //                               ->count();
  //                           $product['thumbnail_count'] = (string)$thumbnailCount;
                            
  //                           // Generate HTML button langsung dari controller
  //                           if ($thumbnailCount < 3) {
  //                               $product['add_thumbnail_button'] = '<button class="btn-action btn-add" onclick="addProductThumbnail(\'' . $product['id'] . '\')" title="Tambah Foto (' . $thumbnailCount . '/3)">
  //                                                       <i class="fa fa-plus"></i>
  //                                                   </button>';
  //                           } else {
  //                               $product['add_thumbnail_button'] = ''; // Kosong jika sudah 3
  //                           }
                            
  //                           // Debug log
  //                           error_log("Product ID: {$product['id']}, Thumbnail Count: $thumbnailCount");
  //                       } catch (\Exception $e) {
  //                           // If table doesn't exist or error, default to allow adding
  //                           error_log("Thumbnail count error: " . $e->getMessage());
  //                           $product['thumbnail_count'] = '0';
  //                           // Allow adding if error
  //                           $product['add_thumbnail_button'] = '<button class="btn-action btn-add" onclick="addProductThumbnail(\'' . $product['id'] . '\')" title="Tambah Foto (0/3)">
  //                                                       <i class="fa fa-plus"></i>
  //                                                   </button>';
  //                       }
  //                   }
  //                   unset($product); // Break reference
  //               }






  //            $tokoId = $this->Storage('toko')
  //               ->select(["nama_toko",'username_toko','logo_toko'])
  //               ->where('userid', 1)
  //               ->first();
  //              $produ = [
  //                ...$online,
  //                ...$tokoId,
  //                "products"=>$totalProducts,
  //                "products_toko"=>$orderData,
  //              ];
        // Get toko data using Product model
       $baru=  $this->useModels('Product', 'Baru', [6]);
        return $baru ?? [];


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
        return [
            'status' => 'success',
            'message' => 'Test data created successfully',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
    }

    /**
     * Red endpoint
     */
    public function red($data = [], $params = []): array
    {
        return [
            'status' => 'success',
            'message' => 'Test data createdssssssssssssss successfully',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
        
        /*
        {
            "status": "success",
            "message": "Test data createdssssssssssssss successfully",
            "timestamp": 1764229412,
            "data": {
                "id": "uniqueId",
                "key": "0995784a34d3ee7dad68ad0bdb629a8f",
                "avatar": "http://192.168.1.17/dev/assets/drive/avatar/2025/11/avatar_1_1764163248_4ffbaafd.jpeg",
                "dashboard_url": "http://192.168.1.17/dev/iandantrik",
                "email": "admin@gmail.com",
                "last_activity": 1764175326,
                "login_time": 1764175326,
                "message": "Login berhasil",
                "password": "N12345678",
                "redirect": "iandantrik",
                "role": "admin",
                "status": "admin",
                "success": true,
                "user_id": 1,
                "user_name": "iandantrik",
                "user_real_name": "iandantrik",
                "userid": 1,
                "createdAt": "2025-11-26T16:42:07.354Z",
                "updatedAt": "2025-11-26T16:42:07.354Z"
            },
            "params": {
                "endpoints": "/api/test",
                "status": true,
                "authorization": "NX_XXXXXXXXXXXXXXXXX",
                "expired": false,
                "allowed_methods": []
            }
        }
        */
    }
    /**
     * Updated endpoint for PUT/PATCH requests
     */
    public function updated($data = [], $params = []): array
    {
        return [
            'status' => 'success',
            'message' => 'Test data updated successfully',
            'timestamp' => time(),
            'data' => $data,
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