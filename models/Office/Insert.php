<?php
declare(strict_types=1);

namespace App\Models\Office;
use App\System\NexaModel;
use App\Models\Office\Upload;

/**
 * Custom exception for file upload operations
 */
class FileUploadException extends \Exception {}

/**
 * Insert class untuk operasi insert data dengan dukungan file upload
 */
class Insert extends NexaModel
{

    /**
     * Build arithmetic formula for field calculations
     * Supports field-to-field operations, field-to-value operations, and complex nested formulas
     */
    public function buildInsert(array $params,array $foreign=null): array {
      $results      = [];
      $forigin      = $foreign;
      // Persist shared ID from the first insert across subsequent inserts
      $sharedId     = null;


    foreach ($params as $key => $item) {
        try {
            $tabelkey    = $item['key'];
            $tabel       = $this->tablesIndex($item['key']) ?? null;
            $data        = $item['insert'] ?? [];
            $dataIndex    = $item['insertIndex'] ?? [];
            $fieldConfig    = $item['insertfile'] ?? [];
            
            // ✅ INTEGRASI FILE UPLOAD: Process file uploads jika ada fieldConfig file
            if (!empty($fieldConfig) && $this->hasFileFields($fieldConfig)) {
                $uploadResult = $this->processFileUploads($tabelkey, $tabel, $data, $fieldConfig);
                if ($uploadResult['success']) {
                    $data = $uploadResult['processedData'];
                } else {
                    throw new FileUploadException('File upload failed: ' . $uploadResult['error']);
                }
            }
          
            if ($key==0) {
                // Merge data dan dataIndex untuk insert (hanya jika dataIndex memiliki nilai)
                $mergedData = $data;
                if (!empty($dataIndex)) {
                    $mergedData = array_merge($data, $dataIndex);
                }
                $sharedId = $this->StorageLast($tabel, $mergedData);
             } else {
                 // Cek apakah $forigin ada dan tidak kosong
                 if (!empty($forigin) && is_array($forigin)) {
                     foreach ($forigin as $value) {
                         if ($tabelkey == $value['key']) {
                             // Validasi tabel berdasarkan key
                             $foreignTable = $this->tablesIndex((int)$value['key']);
                             if ($foreignTable) {
                                 // Tambahkan shared ID ke data berdasarkan failed array
                                 $insertData = $data;
                                 if (isset($value['failed']) && is_array($value['failed'])) {
                                     foreach ($value['failed'] as $fieldName) {
                                         $insertData[$fieldName] = $sharedId;
                                     }
                                 }
                                 $this->Storage($foreignTable)->insert($insertData);
                                
                             }
                         }
                     }
                 } else {
                     // Jika $forigin null atau kosong, lakukan insert tabel normal
                    $this->Storage($tabel)->insert($data);
                 }
             }
           
            $results[] = [
                'success' => true,
                'fieldConfig' => $params,
                'message' => 'Inserted successfully'
            ];
        } catch (\Throwable $e) {
            $results[] = [
                'key'     => $item['key'] ?? null,
                'success' => false,
                'message' => 'Insert operation failed: ' . $e->getMessage()
            ];
        }
    }

    return [
        'success'   => true,
        'results'   => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ];  
    }

    /**
     * Check if fieldConfig contains file fields
     */
    private function hasFileFields(array $fieldConfig): bool {
        foreach ($fieldConfig as $field) {
            if (isset($field['name']) && !empty($field['name'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Process file uploads using Upload class
     */
    private function processFileUploads(int $tabelkey, string $tabel, array $data, array $fieldConfig): array {
        try {
            $upload = new Upload();
            
            // Call Upload->file() dengan format yang diharapkan: [[$key => $name], $columns, $fieldConfig]
            return $upload->file([[$tabelkey => $tabel], $data, $fieldConfig]);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processedData' => $data
            ];
        }
    }
}
