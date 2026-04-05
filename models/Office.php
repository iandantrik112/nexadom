<?php
declare(strict_types=1);

namespace App\Models;

use App\System\NexaModel;
use InvalidArgumentException;
use App\Models\Office\Analysis;
use App\Models\Office\Chart;
use App\Models\Office\Progres;
use App\Models\Office\Formula;
use App\Models\Office\JoinTabel;
use App\Models\Office\SingleTabel;
use App\Models\Office\User;
use App\Models\Office\Insert;
use App\Models\Office\Update;
use App\Models\Office\Upload;
use App\Models\Office\Delete;
use App\Models\Office\TabelView;
use App\Models\Office\CreateTabel;
use App\Models\Office\MergeTabel;
use App\Models\Office\Importing;
use App\Models\Office\Ekstrak;


/**
 * Store - Store model yang extends NexaModel untuk database operations
 */
class Office extends NexaModel
{
    private $formula;

    public function __construct() {
        parent::__construct();
        $this->formula = new Formula();
    }

    // Constants for content validation

    public function byUser(): array {
        $result=$this->Storage('user') 
            ->select([
                'id', 
                'nama', 
                'email', 
                'password',
                'status',
                'telepon',
                'alamat',
                'gender',
                'token',
                'jabatan',
                'instansi',
                'expired'
            ])
            ->where('id', $this->userid())
            ->first();
        return $result ?? [];
    }
    public function flag(): array {
        $result=$this->Storage('wilayah') 
            ->select(['*'])
            ->get();
        return $result ?? [];
    }

    public function upUser(array $data): array {
           // Remove userid from data since it's already used in WHERE clause
           // and the column doesn't exist in the table
           unset($data['userid']);
    
           $result = $this->Storage('user')
               ->where('id', $this->userid())
               ->update($data);
           return ['success' => $result];
    }

    /**
     * Find record by ID
     */
    public function find(string $table, int $id) {
       return $this->Storage($table) 
         ->where('id', $id)
         ->orderBy("id", "DESC")
         ->get();
    }
    // Define table aliases for easy access✅
    // Static property untuk menyimpan TABLE_ALIASES
    private static $tableAliases = null;
    
    // Method untuk mendapatkan TABLE_ALIASES
    private function getTableAliases(): array {
        if (self::$tableAliases === null) {
            $metadata = $this->Storage('controllers')
                ->select(['data'])
                ->where('categori', 'Metadata')->first();
            
            // Check if metadata exists and has data
            if ($metadata && isset($metadata['data']) && is_array($metadata['data'])) {
                $data = $metadata['data'];
                self::$tableAliases = array_combine(
                    array_column($data, 'index'),
                    array_column($data, 'alis')
                );
            } else {
                // Return empty array if no metadata found
                self::$tableAliases = [];
            }
        }
        
        return self::$tableAliases;
    }


    public function avatar(array $data): array{
        $result=$this->Storage('user') 
            ->select("nama,avatar,email")
            ->where('id', $data['avaratid'])
            ->first();
        return $result ?? [];
    }
    //Fix ✅
    public function tablesMeta() {
       $metadata = $this->Storage('controllers')
        ->select(['data as store'])
        ->where('categori', 'Metadata')->first();
        
        // Ensure we return a proper structure even if metadata is null
        if (!$metadata) {
            return ['store' => []];
        }
        
        // Ensure store is always an array
        if (!isset($metadata['store']) || !is_array($metadata['store'])) {
            $metadata['store'] = [];
        }
        
        return $metadata;
    }

    //Fix ✅
    public function tablesShow() {
        $tables = $this->showTables();
        return $tables;
    }

   //Fix ✅
    public function tablesRet() {
        return $this->generateTableMenu($this->getTableAliases());
    }

   //Fix ✅
    public function tablesInfo() {
        return $this->showTablesRetInfo($this->getTableAliases());
    }
   //Fix ✅
    public function tabelVariables($key,$name) {
        
        return $this->showVariablesList([$key => $name]);
    }

    public function tabelVariablesType($key,$name) {
        
        return $this->getVariablesType([$key => $name]);
    }
// public function showTablesRetData(
//     array $indexes = [],         // Parameter 1: Index tabel
//     $limit = 10,                 // Parameter 2: Limit data
//     $whereConditions = [],       // Parameter 3: Kondisi WHERE
//     $format = 'array',           // Parameter 4: Format (tidak digunakan)
//     array $columns = [],         // Parameter 5: Kolom yang diambil
//     $orderBy = null,             // Parameter 6: Order by
//     $orderDirection = 'ASC',     // Parameter 7: Order direction
//     $groupBy = []                // Parameter 8: ✅ GROUP BY (BARU!)
// )
   //Fix ✅
    public function tablesRetData($key,$name,$limit=10, $columns=[], $ordering='id',$orderDirection='DESC') {
        return $this->showTablesRetData([$key => $name],$limit, false, 'array', $columns,$ordering,$orderDirection);
    }

    public function tablesLax($key, array $params = []) {
        $allTables = $this->showTables();
        $tableName = $allTables[$key];
        
        $query = $this->Storage($tableName);
        
        // Handle where conditions if provided
        if (isset($params['where']) && is_array($params['where'])) {
            foreach ($params['where'] as $field => $value) {
                $query = $query->where($field, $value);
            }
        }
        
        $users = $query->get();
        return $users;
    }




   //Fix ✅
       public function setRetInsert($key, $name, $columns = [], $fieldConfig = null) {
          try {
            $key = (int)$key;
            
            // ✅ DEBUG: Log parameters received
            // Processing import request
            
            if ($fieldConfig) {
              // ✅ NEW: Check if any field has importing=true
              $hasImportingFields = $this->hasImportingFields($fieldConfig);
              if ($hasImportingFields) {
                try {
                  // Process importing data from Excel/CSV
                  $importingResult = (new Importing())->data([[$key => $name], $columns, $fieldConfig]);
                  // Processing completed
                  
                  if (!$importingResult['success']) {
                    return $importingResult; // Return error if importing failed
                  }
                } catch (\Exception $e) {
                  error_log("Importing class error: " . $e->getMessage());
                  return [
                    'success' => false,
                    'error' => 'Importing failed: ' . $e->getMessage()
                  ];
                }
                
                // ✅ FIX: Gabungkan data asli ($columns) dengan processedData dari Importing
                // Ini memastikan semua field tetap ada, bukan hanya field importing
                $importingProcessedData = $importingResult['processedData'] ?? [];
                $processedData = array_merge($columns, $importingProcessedData);
                
                // ✅ CLEAN: Remove any metadata before database insertion
                $cleanData = $processedData;
                unset($cleanData['fileProcessing']); // Remove if exists
                unset($cleanData['files']); // ✅ FIX: Remove 'files' field (tidak ada di database, hanya metadata)
                unset($cleanData['_isFileUpload']); // ✅ FIX: Remove flag (tidak perlu di database)
                
                // ✅ FIX: Remove _isFileUpload dari semua field yang memiliki flag ini
                foreach ($cleanData as $colKey => $colValue) {
                    if (is_array($colValue) && isset($colValue['_isFileUpload'])) {
                        unset($cleanData[$colKey]['_isFileUpload']);
                    }
                }
                
                // Insert remaining form data (if any) - with validation
                $result = null;
                if (!empty($cleanData) && $this->hasValidFormData($cleanData)) {
                  $result = $this->tablesRetInsert([$key => $name], $cleanData);
                } else {
                  $result = [
                    'success' => true,
                    'message' => 'No additional form data to insert'
                  ];
                }
                
                // ✅ Add importing info to result AFTER database operation
                if (is_array($result)) {
                  $result['importing'] = [
                    'processed' => true,
                    'importedRecords' => $importingResult['importedRecords'] ?? 0,
                    'importedData' => $importingResult['importedData'] ?? [],
                    'message' => $importingResult['message'] ?? 'Data imported successfully'
                  ];
                }
                
                return $result;
              } else {
                // Process file uploads normally (no importing)
                $uploadResult = (new Upload())->file([[$key => $name], $columns, $fieldConfig]);
                
                if (!$uploadResult['success']) {
                  return $uploadResult; // Return error if file processing failed
                }
                
                // ✅ FIX: Gunakan processedData dari Upload yang sudah berisi semua field
                // Upload class sudah memproses file dan mengembalikan semua field dalam processedData
                $processedData = $uploadResult['processedData'] ?? $columns;
                
                // ✅ Pastikan semua field dari $columns tetap ada (jika ada yang hilang)
                // Gabungkan dengan prioritas: processedData menimpa columns untuk field file
                foreach ($columns as $colKey => $colValue) {
                  // Jika field tidak ada di processedData, tambahkan dari columns
                  if (!isset($processedData[$colKey])) {
                    $processedData[$colKey] = $colValue;
                  }
                }
                
                // ✅ CLEAN: Remove any metadata before database insertion
                $cleanData = $processedData;
                unset($cleanData['fileProcessing']); // Remove if exists
                unset($cleanData['files']); // ✅ FIX: Remove 'files' field (tidak ada di database, hanya metadata)
                unset($cleanData['_isFileUpload']); // ✅ FIX: Remove flag (tidak perlu di database)
                
                // ✅ FIX: Remove _isFileUpload dari semua field yang memiliki flag ini
                foreach ($cleanData as $colKey => $colValue) {
                    if (is_array($colValue) && isset($colValue['_isFileUpload'])) {
                        unset($cleanData[$colKey]['_isFileUpload']);
                    }
                }
                
                // Validate form data before insert
                if ($this->hasValidFormData($cleanData)) {
                  $result = $this->tablesRetInsert([$key => $name], $cleanData);
                } else {
                  $result = [
                    'success' => true,
                    'message' => 'No valid form data to insert'
                  ];
                }
                
                // ✅ Add file processing info to result AFTER database operation
                if (is_array($result)) {
                  $result['fileProcessing'] = [
                    'processed' => true,
                    'fieldConfig' => $fieldConfig
                  ];
                }
                
                return $result;
              }
            } else {
              // Validate data before direct insert
              // ✅ FIX: Clean data sebelum insert (remove metadata fields)
              $cleanColumns = $columns;
              unset($cleanColumns['files']); // ✅ FIX: Remove 'files' field (tidak ada di database)
              unset($cleanColumns['_isFileUpload']); // ✅ FIX: Remove flag
              unset($cleanColumns['fileProcessing']); // Remove if exists
              
              // ✅ FIX: Remove _isFileUpload dari semua field yang memiliki flag ini
              foreach ($cleanColumns as $colKey => $colValue) {
                  if (is_array($colValue) && isset($colValue['_isFileUpload'])) {
                      unset($cleanColumns[$colKey]['_isFileUpload']);
                  }
              }
              
              if ($this->hasValidFormData($cleanColumns)) {
                $result = $this->tablesRetInsert([$key => $name], $cleanColumns);
              } else {
                $result = [
                  'success' => true,
                  'message' => 'No valid data to insert'
                ];
              }
            }
            
            return $result;
          } catch (\Exception $e) {
              throw $e; // Re-throw untuk debugging
          }

    }

    
    public function setRetUpdate($key, $name, $columns = [], $id = null, $fieldConfig = null) {
        try {
            $id = (int)$id;
            $key = (int)$key;
            
            // ✅ DEBUG: Log parameters received
            // Processing update request
            
            if ($fieldConfig) {
                // ✅ NEW: Check if any field has importing=true
                $hasImportingFields = $this->hasImportingFields($fieldConfig);
                if ($hasImportingFields) {
                    try {
                        // Process importing data from Excel/CSV
                        $importingResult = (new Importing())->data([[$key => $name], $columns, $fieldConfig]);
                        // Processing completed
                        
                        if (!$importingResult['success']) {
                            return $importingResult; // Return error if importing failed
                        }
                    } catch (\Exception $e) {
                        error_log("Importing class error: " . $e->getMessage());
                        return [
                            'success' => false,
                            'error' => 'Importing failed: ' . $e->getMessage()
                        ];
                    }
                    
                    // ✅ FIX: Gabungkan data asli ($columns) dengan processedData dari Importing
                    // Ini memastikan semua field tetap ada, bukan hanya field importing
                    $importingProcessedData = $importingResult['processedData'] ?? [];
                    $processedData = array_merge($columns, $importingProcessedData);
                    
                    // ✅ CLEAN: Remove any metadata before database update
                    $cleanData = $processedData;
                    unset($cleanData['fileProcessing']); // Remove if exists
                    
                    // Update remaining form data (if any) - with validation
                    $result = null;
                    if (!empty($cleanData) && $this->hasValidFormData($cleanData)) {
                        $result = $this->tablesRetUpdate([$key => $name], $cleanData, $id);
                    } else {
                        $result = [
                            'success' => true,
                            'message' => 'No additional form data to update'
                        ];
                    }
                    
                    // ✅ Add importing info to result AFTER database operation
                    if (is_array($result)) {
                        $result['importing'] = [
                            'processed' => true,
                            'importedRecords' => $importingResult['importedRecords'] ?? 0,
                            'importedData' => $importingResult['importedData'] ?? [],
                            'message' => $importingResult['message'] ?? 'Data imported successfully'
                        ];
                    }
                    
                    return $result;
                } else {
                    // Process file uploads normally (no importing)
                    $uploadResult = (new Upload())->file([[$key => $name], $columns, $fieldConfig]);
                    
                    if (!$uploadResult['success']) {
                        return $uploadResult; // Return error if file processing failed
                    }
                    
                    // ✅ FIX: Gunakan processedData dari Upload yang sudah berisi semua field
                    // Upload class sudah memproses file dan mengembalikan semua field dalam processedData
                    $processedData = $uploadResult['processedData'] ?? $columns;
                    
                    // ✅ Pastikan semua field dari $columns tetap ada (jika ada yang hilang)
                    // Gabungkan dengan prioritas: processedData menimpa columns untuk field file
                    foreach ($columns as $colKey => $colValue) {
                      // Jika field tidak ada di processedData, tambahkan dari columns
                      if (!isset($processedData[$colKey])) {
                        $processedData[$colKey] = $colValue;
                      }
                    }
                    
                    // ✅ CLEAN: Remove any metadata before database update
                    $cleanData = $processedData;
                    unset($cleanData['fileProcessing']); // Remove if exists
                    
                    // Validate form data before update
                    if ($this->hasValidFormData($cleanData)) {
                        $result = $this->tablesRetUpdate([$key => $name], $cleanData, $id);
                    } else {
                        $result = [
                            'success' => true,
                            'message' => 'No valid form data to update'
                        ];
                    }
                    
                    // ✅ Add file processing info to result AFTER database operation
                    if (is_array($result)) {
                        $result['fileProcessing'] = [
                            'processed' => true,
                            'fieldConfig' => $fieldConfig
                        ];
                    }
                    
                    return $result;
                }
            } else {
                // Validate data before direct update
                if ($this->hasValidFormData($columns)) {
                    $result = $this->tablesRetUpdate([$key => $name], $columns, $id);
                } else {
                    $result = [
                        'success' => true,
                        'message' => 'No valid data to update'
                    ];
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            throw $e; // Re-throw untuk debugging
        }
    }

    /**
     * Check if fieldConfig has any fields with importing=true
     */
    private function hasImportingFields($fieldConfig): bool {
        // ✅ FIX: Handle fieldConfig yang mungkin string (JSON dari FormData)
        if (is_string($fieldConfig)) {
            $decoded = json_decode($fieldConfig, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $fieldConfig = $decoded;
            } else {
                return false;
            }
        }
        
        if (!is_array($fieldConfig)) {
            return false;
        }
        
        foreach ($fieldConfig as $field) {
            if (isset($field['importing']) && $field['importing'] === true) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if form data has valid content (not empty)
     */
    private function hasValidFormData(array $data): bool {
        if (empty($data)) {
            return false;
        }
        
        // Count meaningful fields
        $validFields = 0;
        $excludeFields = ['userid', 'id', 'created_at', 'updated_at', 'row'];
        
        foreach ($data as $field => $value) {
            // Skip system fields
            if (in_array($field, $excludeFields)) {
                continue;
            }
            
            // Check if value is meaningful
            if ($this->isValidFieldValue($value)) {
                $validFields++;
            }
        }
        
        // Must have at least 1 valid field
        return $validFields > 0;
    }
    
    /**
     * Check if a field value is valid (not empty-like)
     */
    private function isValidFieldValue($value): bool {
        if (is_null($value)) {
            return false;
        }
        
        if (is_array($value)) {
            return !empty($value);
        }
        
        $stringValue = trim((string)$value);
        
        // List of values considered as empty
        $emptyValues = [
            '', 'NULL', 'null', 'Null', 
            '-', '--', '---', 
            'N/A', 'n/a', 'NA', 'na',
            '0', '0.0', '0,0'
        ];
        
        if (in_array($stringValue, $emptyValues)) {
            return false;
        }
        
        // Check if it's only whitespace or meaningless characters
        if (preg_match('/^[\s\-_.,;:]*$/', $stringValue)) {
            return false;
        }
        
        return strlen($stringValue) > 0;
    }

   //Fix ✅
    public function tablesRetCount($key,$name, $columns) {
        return $this->showTablesRetGroup([$key => $name],$columns);
    }

   //Fix ✅
    public function setRettDelete($key,$name, $id) {
    
      try {
            $id = (int)$id;
            $key = (int)$key;
            $result = $this->tablesRetDelete([$key => $name], $id);
            return $result;
        } catch (\Exception $e) {
            throw $e; // Re-throw untuk debugging
        }
        //return $this->tablesRetDelete([$key => $name],$id);
    }



    public function setRetFind($key, $name,$id=null) {
        return $this->tablesRetFind([$key => $name],$id);
    }


   public function backedTabelView(array $data): array{
         $setData=[
           'user_id'=>1,
           'status'=>'tabelView',
           'data_type'=>'tabelView',
           'data_value'=>$data['data'],
         ];
             $setAtFind= $this->Storage('nexa_office')
            ->where('data_type', 'tabelView')
            ->first();
            if ($setAtFind && isset($setAtFind['id'])) {
                 $this->Storage('nexa_office')
                    ->where('data_type','tabelView')
                    ->update($setData);
            } else {
               $this->Storage('nexa_office')->upsert($setData);
            }

       return [
           'success' =>true,
           'timestamp' => date('Y-m-d H:i:s')
       ];
    }

   public function getTabelViewss(array $data): array{

       return [
           'success' =>true,
           'response' =>$data,
           'timestamp' => date('Y-m-d H:i:s')
       ];
   }



   public function getTabelView(array $data): array{
         $setAtFind= $this->Storage('nexa_office')
         ->select(['data_value'])
        ->where('data_type', 'tabelView')
        ->first();
       return [
           'success' =>true,
           'response' =>$setAtFind['data_value'],
           'timestamp' => date('Y-m-d H:i:s')
       ];
   }




   public function backedCreateTabel(array $data): array{
         $setData=[
           'user_id'=>1,
           'status'=>'createTabel',
           'data_type'=>'createTabel',
           'data_value'=>$data['data'],
         ];
             $setAtFind= $this->Storage('nexa_office')
            ->where('data_type', 'createTabel')
            ->first();
            if ($setAtFind && isset($setAtFind['id'])) {
                 $this->Storage('nexa_office')
                    ->where('data_type','createTabel')
                    ->update($setData);
            } else {
               $this->Storage('nexa_office')->upsert($setData);
            }

       return [
           'success' =>true,
           'timestamp' => date('Y-m-d H:i:s')
       ];
    }



   public function getCreateTabel(array $data): array{
         $setAtFind= $this->Storage('nexa_office')
         ->select(['data_value'])
        ->where('data_type', 'createTabel')
        ->first();
       return [
           'success' =>true,
           'response' =>$setAtFind['data_value'],
           'timestamp' => date('Y-m-d H:i:s')
       ];
   }
   public function shareWithuser(array $data): array{
       $setData=[
         'user_id'=>$data['users'],
         'to_id'=>$data['tousers'],
         'status'=>'share',
         'data_type'=>'share',
         'data_value'=>$data,
       ];
       $result = $this->Storage('nexa_office')->upsert($setData);
       return [
           'success' =>true,
           'timestamp' => date('Y-m-d H:i:s')
       ];
    }
   public function setApps(array $data): array{
         $setData=[
           'user_id'=>$this->userid(),
           'status'=>'Apps',
           'title'=>$data['appname'] ?? null,
           'description'=>$data['description'] ?? null,
           'version'=>$data['version'] ?? '1.0.0',
           'data_type'=>'Apps' ?? null,
           'data_value'=>$data ?? null,
         ];
             $setAtFind= $this->Storage('nexa_office')
            ->where('data_type', 'Apps')
            ->first();
            if ($setAtFind && isset($setAtFind['id'])) {
                 $this->Storage('nexa_office')
                    ->where('data_type','Apps')
                    ->update($setData);

            } else {
               $this->Storage('nexa_office')->upsert($setData);
            }
            
       return [
           'response' =>$setAtFind ?? null,
           'success' =>true,
           'timestamp' => date('Y-m-d H:i:s')
       ];
    }


   public function restful(array $data): array{
         $setData=[
           'user_id'=>$this->userid(),
           'status'=>'API',
           'title'=>$data['appname'] ?? null,
           'description'=>$data['description'] ?? null,
           'version'=>$data['version'] ?? '1.0.0',
           'authorization'=>$data['authorization'] ?? null,
           'data_type'=>$data['endpoind'] ?? null,
           'data_value'=>$data['data'] ?? null,
           'buckets'=>$data['storage'] ?? null,
         ];
             $setAtFind= $this->Storage('nexa_office')
            ->select(['id', 'authorization'])
           
            ->where('status', 'API')
            ->where('data_type',$data['endpoind'])
            ->first();
            if ($setAtFind && isset($setAtFind['id'])) {
                 $this->Storage('nexa_office')
                    ->where('status', 'API')
                    ->where('data_type',$data['endpoind'])
                    ->update($setData);

            } else {
               $this->Storage('nexa_office')->upsert($setData);
            }
            
       return [
           'response' =>$setAtFind ?? null,
           'success' =>true,
           'timestamp' => date('Y-m-d H:i:s')
       ];
    }




    public function shareID() {
        $Data= $this->Storage('nexa_office')
        ->select('id,to_id AS userid ,data_value AS data')
        ->where('to_id', $this->userid())
        ->where('data_type','share')
        ->get();
        return $Data;
    }
    public function shareDel(array $data): array{
            $this->Storage('nexa_office')
             ->where('to_id', $this->userid())
            ->where('id', $data['id'])
            ->delete();
        return [
           'success' =>true,
           'timestamp' => date('Y-m-d H:i:s')
       ];
    }









public function upNavigation(array $params): array {
    $this->Storage('nexa_office')
    ->where('data_type','Route')
    ->update([
        'data_value'=>$params,
    ]);



          $data = $this->Storage('nexa_office')
            ->select(['data_value','navigasi','appname','icon'])
            ->where('data_type', 'Route')
            ->first();
        
        $brandConfig = [
            'href' =>'home',
            'logo' =>$data['icon'] ?? '/assets/images/favicon.png',
            'alt'  =>$data['appname'] ?? 'NexaUI',
            'text' =>$data['appname'] ?? 'NexaUi'
        ];
 
          $nexaJon = $this->redJson();
          $nexaJon->setData([
            'type' => $data['navigasi'] ?? 'Standard', // Children
            'menuData' => $data['data_value']['main_menu'],
            'brandConfig' => $brandConfig,
          ]);
          $saved = $nexaJon->save('menu_config.json');





    return $params;
}


    public function setRetFindKey($key, $name,$failed,$id=null) {
        return $this->tablesRetFindKey([$key => $name],$failed,$id);
    }

    public function setAtGroupObj(array $data): ?array {
        try {
             $tableIndex = array_keys($data['key'])[0];
              $columns=$data['columns'];
              $access=$data['access'] ?? '';
              $allTables = $this->showTables();
              $tableName = $allTables[$tableIndex];
             if ($access=="private") {
              $result=$this->Storage($tableName)
                ->select($columns)
                ->where('userid', $this->userid())
                ->groupBy($columns)
                ->limit(100)
                ->get();
             } else {
               $result=$this->Storage($tableName)
                ->select($columns)
                ->groupBy($columns)
                ->limit(100)
                ->get();
             }

            return $result;
        } catch (\Exception $e) {
           return [
            'success'=>false,
            'data'=>[]
           ];
        }
    } 

   public function setAtGroup($key, $name, $columns,$access='') {
    // 261760199266386 title (2) ['nama', 'jabatan']
    try {
        $key = (int)$key;
        
        // Check if table exists
        $allTables = $this->showTables();
        if (!isset($allTables[$key])) {
            return $key;
        }
        
        // Ensure access parameter is a string, default to empty string if null
        $access = $access ?? '';
        
        // Use the updated firstAtGroup method that handles both string and array columns
        $result = $this->firstAtGroup([$key => $name], $columns, $access);
        
        return $result;
    } catch (\Exception $e) {
       return [
        'success'=>false,
        'data'=>[]
       ];
    }
} 



   public function searchAt(array $data,string $keyword): array{
        return $this->searchAtFind($data,$keyword);
    }




public function setAtFind(array $data, string $keyword): array
{
    $result = $this->firstAtFind($data, $keyword);

    // pastikan selalu array
    if (!$result) {
        return []; // kosong jika tidak ada data
    }

    return $result;
}


public function nestedAnalysis(array $bulder): array {
   $Data = new Analysis();
   return $Data->index($bulder);
}

/**
 * Direct analysis method - accepts the same format as nestedAnalysis
 * This method calls Analysis::directAnalysis() which is a wrapper for Analysis::index()
 * 
 * @param array $data Query data (same format as nestedAnalysis)
 * @param array $analysisConfig Optional analysis configuration (where, group, order, limit, offset, etc.)
 * @return array Analysis result
 */
public function directAnalysis(array $data, array $analysisConfig = []): array {
   $Data = new Analysis();
   return $Data->directAnalysis($data, $analysisConfig);
}

/**
 * Process nestedAnalysis result and perform additional analysis
 * This method calls Analysis::fromNestedAnalysis()
 * 
 * @param array $nestedResult Result from nestedAnalysis method
 * @param array $originalQueryData Original query data used for nestedAnalysis (optional, but recommended)
 * @param array $analysisConfig Additional analysis configuration (where, group, order, etc.)
 * @return array Analysis result
 */
public function fromNestedAnalysis(array $nestedResult, array $originalQueryData = [], array $analysisConfig = []): array {
   $Data = new Analysis();
   return $Data->fromNestedAnalysis($nestedResult, $originalQueryData, $analysisConfig);
}

// public function executeAnalysis(array $data) {
//    $joinTabel = new Analysis();
//    return $joinTabel->setAnalysis($data);
// }



  public function fileEkstrak(array $data): array {
    return (new Ekstrak())->index($data);
  }

// public function nestedAnalysisProgres(array $bulder): array {
//   return (new Progres())->NestedTabel($bulder);
// }

// public function crossJoinAnalysisProgres(array $bulder): array {
//   return (new Progres())->CrossJoin($bulder);
// }
/**
 * Execute complex JOIN operation like JoinTabel.js configuration
 * Supports SELECT, UPDATE, DELETE, INSERT operations with JOINs
 */
public function executeOperation(array $data) {
   $joinTabel = new JoinTabel();
   return $joinTabel->joinQuery($data);
}
/**
 * Method khusus untuk operasi SUM dan COUNT pada single table
 * Optimized untuk agregasi data numerik
 */
public function standaloneAt(array $bulder): array {
   return (new SingleTabel())->singleQuery($bulder); 
}



private function buckrTargetId(string $sql): int {
    $result = $this->raw($sql);
    return isset($result[0]['id']) ? (int)$result[0]['id'] : 0;
}


private function buckUpdateDirect(string $table, int $id, array $payload): void {
    if (!is_array($payload) || empty($payload)) {
        throw new InvalidArgumentException("Update payload is empty or invalid.");
    }

    $this->Storage($table)
         ->where('id', $id)
         ->update($payload);
}

public function buckUpdate(array $params, array $foreign = null): array {
    return (new Update())->buildUpdate($params, $foreign);
}

public function buckInsert(array $params,array $foreign=null): array {
  // return $params;
     return (new Insert())->buildInsert($params,$foreign);
}

public function buckDelete(array $params, array $foreign = null): array {
    return (new Delete())->buildDelete($params, $foreign);
}

    public function buckTabelView(array $data): array {
        return (new TabelView())->buildTabelView($data);
    }







    

    public function delTabelView(array $data): array {
        return (new TabelView())->buildTabelDelete($data);
    }

    public function testTabelView(array $data): array {
        return (new TabelView())->testTabelView($data);
    }

    public function buckCreateTabel(array $data): array {
        return (new CreateTabel())->buildCreateTabel($data);
    }

    public function alterBuckCreateTabel(array $data): array {
        return (new CreateTabel())->alterCreateTabel($data);
    }

    public function dropBuckCreateTabel(array $data): array {
        return (new CreateTabel())->dropCreateTabel($data);
    }





    public function buckMergeTabel(array $data): array {
        return (new MergeTabel())->buildCreateTabel($data);
    }



    public function dropbuckMergeTabel(array $data): array {
        return (new MergeTabel())->dropCreateTabel($data);
    }




public function bucketsSystem(array $params): array {
   $result = $this->Storage('nexa_office')->insert($params);
   return [
       'success' => (bool)$result,
       'data' => $params,
       'timestamp' => date('Y-m-d H:i:s')
   ];
}
public function upBucketsSystem(array $params): array {
    $this->Storage('nexa_office')
    ->where('data_key',$params['version'])
    ->update([
        'version'=>$params['version'],
        'data_value'=>$params['data'],
    ]);
    return $params;
}
public function getBucketsSystem(array $params): array {
    $data= $this->Storage('nexa_office')
    ->select(['version', 'data_value'])
    ->where('version', $params['version'])
    ->first();
     //  //   
     //  // ->where('version','1.0.1')
     //  ->get();
  return $data;
}

private function buckDeleteDirect(string $table, int $id): void {
    // $this->Storage($table)
    //      ->where('id', $id)
    //      ->delete();
}

public function Buckets(array $params): array {
    $data    = $params['data'] ?? [];
    $id      = $params['id'] ?? null;
    $payload = $params['update'] ?? [];
    $mode    = $params['mode'] ?? 'update'; // default: update

    $requiredKeys = [
        'keyindexname', 'targetkey', 'keyindex', 'keytarget',
        'groupJoinType', 'groupJoinCondition', 'index', 'target'
    ];

    if ($mode === 'update') {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException("Missing required key: {$key}");
            }
        }

        if (!empty($data['keyindex'])) {
            $sql = $this->buildBuckets($data, $id);
            $targetIndex = (int)$data['keytarget'];
            $targetTable = $this->tablesIndex($targetIndex);
            $targetId    = $this->buckrTargetId($sql);
            $this->buckUpdateDirect($targetTable, $targetId, $payload);
        } else {
            $mainTable = $this->tablesIndex($data['key'] ?? null);
            $mainId    = (int)$id;
            $this->buckUpdateDirect($mainTable, $mainId, $payload);
        }

     // } elseif ($mode === 'insert') {
     //     $table = $this->tablesIndex($data['key'] ?? null);
     //    // $newId = $this->buckInsert($table, $payload);
     //     return [
     //         'success'   => true,
     //         'tabel'   => $table,
     //         'payload'   => $payload,
     //        // 'inserted_id' => $newId,
     //         'timestamp' => date('Y-m-d H:i:s')
     //     ];
    } elseif ($mode === 'delete') {
        $table = $this->tablesIndex($data['key'] ?? null);
        $deleteId = (int)$id;
        $this->buckDeleteDirect($table, $deleteId);
    }

    return [
        'success'   => true,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}



private function buildBuckets(array $data, ?string $id = null): string {
    $indexTable   = (int)$data['keyindex'];
    $fromAlias    = $data['keyindexname'];
    $baseTable    = $this->tablesIndex($indexTable) . " AS {$fromAlias}";

    $joinTypeRaw  = strtoupper(trim($data['groupJoinType']));
    $joinType     = match ($joinTypeRaw) {
        'LEFT'  => 'LEFT JOIN',
        'RIGHT' => 'RIGHT JOIN',
        'INNER' => 'INNER JOIN',
        'FULL'  => 'FULL OUTER JOIN',
        default => throw new InvalidArgumentException("Unsupported join type: {$joinTypeRaw}")
    };
    $leftField    = str_replace('-', '.', $data['index']);
    $rightField   = str_replace('-', '.', $data['target']);
    $joinCondition = "{$leftField} {$data['groupJoinCondition']} {$rightField}";
    $targetIndex  = (int)$data['keytarget'];
    $targetAlias  = $data['targetkey'];
    $targetTable  = $this->tablesIndex($targetIndex) . " AS {$targetAlias}";
    $sql = "SELECT {$targetAlias}.id FROM {$baseTable} {$joinType} {$targetTable} ON {$joinCondition}";

    if (!empty($id)) {
        $sql .= " WHERE {$fromAlias}.id = '" . addslashes($id) . "'";
    }
    return $sql;
}

 



// batas class
}