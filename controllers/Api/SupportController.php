<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * Text Controller untuk API endpoints
 */
class SupportController extends NexaController
{
    /**
     * Text endpoint
     */
    public function index(): array
    {
        return [
            'status' => 'success',
            'message' => 'Text API endpoint working!',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s'),
            'data' => [
                'text' => true,
                'version' => '1.0.0'
            ]
        ];
    }

    public function properti($data= []): array
    {
        $setAtFind= $this->Storage('nexa_office')
         ->select(['data_value AS properti'])
        ->where('data_type', 'Apps')
        ->first();
        return $setAtFind['properti'] ?? [];
    }
    /**
     * Created endpoint for POST requests
     */
    public function red($data = [], $params = []): array
    {
         // Validate required parameter
         if (!isset($data['id']) || empty($data['id'])) {
             return [
                 'status' => 'error',
                 'code' => 400,
                 'message' => 'User ID is required',
                 'timestamp' => time(),
                 'data' => [
                     'error' => 'Missing or invalid user ID parameter',
                     'received_data' => $data
                 ]
             ];
         }
         

          $setApps= $this->Storage('nexa_office')
           ->select(['title','version','description','data_value AS properti'])
          ->where('data_type', 'Apps')
          ->where('status', 'production')
          ->first();
         $appname=$setApps['title']??'NexaUI';
         $version=$setApps['version']??'1.0.0';
         $getUserId =$data['id'];    
         try {
             $accessToken = $this->Storage('user')
             ->select(["nama", "token","email",'avatar',"kecamatan","desa"])
             ->where("id", $getUserId)
             ->first();
         } catch (\Exception $e) {
             return [
                 'status' => 'error',
                 'code' => 500,
                 'message' => 'Database error while fetching user data',
                 'timestamp' => time(),
                 'data' => [
                     'error' => $e->getMessage()
                 ]
             ];
         }
          if ($accessToken && isset($accessToken['token']) && $accessToken['token']) {
              $status =1;
          } else {
              $status = 0;
          }
    
         try {
             $System=$this->Storage('nexa_office')
             ->select(['data_key as updated', 'updated_at','data_value AS packages'])
             ->where('data_type','System')
              ->orderBy('id', 'DESC')
             ->first();
         } catch (\Exception $e) {
             return [
                 'status' => 'error',
                 'code' => 500,
                 'message' => 'Database error while fetching system data',
                 'timestamp' => time(),
                 'data' => [
                     'error' => $e->getMessage()
                 ]
             ];
         }

         try {
             $access = $this->Storage('controllers')
             ->select(["acdelete","acpublik","approval","pintasan","acupdate","acmenu", "label AS variable","kecamatan","desa","acinsert"])
             ->where("categori","Accses")
             ->where("userid", $getUserId)
             ->get();
         } catch (\Exception $e) {
             return [
                 'status' => 'error',
                 'code' => 500,
                 'message' => 'Database error while fetching access data',
                 'timestamp' => time(),
                 'data' => [
                     'error' => $e->getMessage()
                 ]
             ];
         }

         // Initialize arrays
         $accessData = [];
         $acpublik = [];
         $pintasan = [];
         $approval = [];
         $acupdate = [];
         $acmenu = [];
         $kecamatan = [];
         $territory = [];
         $accessAdd = [];
         
         // Process access data in a single loop
         if (is_array($access) && count($access) > 0) {
             foreach ($access as $item) {
                 $variable = $item['variable'];
                 $acmenu[$variable] = $item['acmenu'] ?? false;
                 $accessData[$variable] = $item['acdelete'] ?? false;
                 $accessAdd[$variable]  = $item['acinsert'] ?? false;
                 $acpublik[$variable]   = $item['acpublik'] ?? false;
                 $pintasan[$variable]   = $item['pintasan'] ?? false;
                 $acupdate[$variable]   = $item['acupdate'] ?? false;
                 $approval[$variable]   = $item['approval'] ?? false;
                 $kecamatan[$variable]  = ($item['kecamatan'] == 1) ? ($accessToken['kecamatan'] ?? false) : false;
        
                // Create nested structure for desa
                $territory[$variable] = [
                    'kecamatan' => $kecamatan[$variable],
                    'desa' => ($item['desa'] == 1) ? ($accessToken['desa'] ?? false) : false
                ];
             }
         } 

      
        // Set JavaScript controller data untuk akses di frontend
        // Data ini akan tersedia di NEXA.controllers.data dan bisa diakses di browser console
       return  [

           'version'      => $System,
           'assets'        =>array(
              'redaccess'    => $accessData,
              'accessData'   => $acmenu,
              'accessAdd'    => $accessAdd,
              'access'       => $acpublik,
              'approval'     => $approval,
              'pintasan'     => $pintasan,
              'upaccess'     => $acupdate,
              'territory'    => $territory,
              'appname'      => $appname,
              'assets'       => $this->url('/assets'),
              'username'     => $accessToken['nama'],
              'useremail'    => $accessToken['email'] ?? '',
              'avatar'       => $this->url('/assets/drive/'.$accessToken['avatar']),
              'appversion'   => $version,
              'token_status' => $status,
           ),
           'packages'     =>$System['packages'][$System['updated']]['application'],
        ]; 
    }   
    /**
     * Created endpoint for POST requests
     */
    public function created($data = [], $params = []): array
    {
        return [
            'status' => 'success',
            'message' => 'Text created successfully',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
    }
    
    /**
     * Updated endpoint for PUT/PATCH requests
     */
    public function updated($data = [], $params = []): array
    {
        return [
            'status' => 'success',
            'message' => 'Text updated successfully',
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
            'message' => 'Text deleted successfully',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
    }
}

