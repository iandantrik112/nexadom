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
 * Update class untuk operasi update data dengan dukungan file upload
 */
class Update extends NexaModel
{

    /**
     * Batch update records across tables using table key mapping.
     * params[] structure per item:
     * - key: int (required) -> tablesIndex lookup
     * - id: int (required for direct update; optional if foreign match provided)
     * - update: array payload fields
     * - updateIndex: array extra fields to merge (optional)
     * - updatefile: array file fields config (optional)
     */
    public function buildUpdate(array $params,array $foreign=null): array {
      $results      = [];
      $forigin      = $foreign;


    foreach ($params as $key => $item) {
        try {
            $tabelkey    = $item['key'];
            $tabel       = $this->tablesIndex($item['key']) ?? null;
            $id          = isset($item['id']) ? (int)$item['id'] : null;
            $data        = $item['update'] ?? [];
            // Support id passed inside payload and avoid updating primary key column
            if ($id === null && isset($data['id'])) {
                $id = (int)$data['id'];
                unset($data['id']);
            }
            $providedSharedId = isset($item['sharedId']) ? (int)$item['sharedId'] : null;
            $dataIndex   = $item['updateIndex'] ?? [];
            $fieldConfig = $item['updatefile'] ?? [];
            
            // ✅ INTEGRASI FILE UPLOAD: Process file uploads jika ada fieldConfig file
            if (!empty($fieldConfig) && $this->hasFileFields($fieldConfig)) {
                $uploadResult = $this->processFileUploads($tabelkey, $tabel, $data, $fieldConfig);
                if ($uploadResult['success']) {
                    $data = $uploadResult['processedData'];
                } else {
                    throw new FileUploadException('File upload failed: ' . $uploadResult['error']);
                }
            }
            
            // Determine primary shared id from first item
            static $sharedId = null;
            if ($key === 0) {
                // Case A: direct update by id for the first/only item
                if ($id !== null) {
                    $mergedData = !empty($dataIndex) ? array_merge($data, $dataIndex) : $data;
                    $sharedId = $id;
                    // If no fields supplied, treat as no-op but keep sharedId available for children
                    if (!empty($mergedData)) {
                        $this->Storage($tabel)
                            ->where('id', $sharedId)
                            ->update($mergedData);
                    }
                // Case B: allow standalone child update by FK using provided sharedId + foreign mapping
                } else if ($providedSharedId !== null && !empty($forigin)) {
                    $sharedId = $providedSharedId;
                    $matched = false;
                    foreach ($forigin as $value) {
                        if ($tabelkey == $value['key'] && isset($value['failed'][0])) {
                            $fkField = $value['failed'][0];
                            $mergedData = !empty($dataIndex) ? array_merge($data, $dataIndex) : $data;
                            $this->Storage($tabel)
                                ->where($fkField, $sharedId)
                                ->update($mergedData);
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        throw new \InvalidArgumentException('Unable to resolve foreign mapping for standalone child update');
                    }
                } else {
                    throw new \InvalidArgumentException('Missing id or sharedId for primary update item');
                }
            } else {
                // Subsequent items
                if (!empty($forigin) && is_array($forigin)) {
                    $handled = false;
                    foreach ($forigin as $value) {
                        if ($tabelkey == $value['key']) {
                            $foreignTable = $this->tablesIndex((int)$value['key']);
                            if ($foreignTable) {
                                $updateData = $data;
                                if (isset($updateData['id'])) {
                                    unset($updateData['id']);
                                }
                                // If mapping fields provided, ensure they carry the shared id
                                if (isset($value['failed']) && is_array($value['failed'])) {
                                    foreach ($value['failed'] as $fieldName) {
                                        $updateData[$fieldName] = $sharedId;
                                    }
                                }
                                // If id provided for this child, update by id; otherwise update by FK = sharedId
                                if (isset($item['id'])) {
                                    $this->Storage($foreignTable)
                                        ->where('id', (int)$item['id'])
                                        ->update($updateData);
                                } else if (isset($value['failed'][0])) {
                                    $fkField = $value['failed'][0];
                                    $this->Storage($foreignTable)
                                        ->where($fkField, $sharedId)
                                        ->update($updateData);
                                }
                                $handled = true;
                            }
                        }
                    }
                    if (!$handled) {
                        // Fallback: if no foreign rule matched, and an id is present, update directly
                        if ($id !== null) {
                            $this->Storage($tabel)
                                ->where('id', $id)
                                ->update($data);
                        }
                    }
                } else {
                    // No foreign rules; update directly if id provided
                    if ($id !== null) {
                        $this->Storage($tabel)
                            ->where('id', $id)
                            ->update($data);
                    }
                }
            }
           
            $results[] = [
                'success' => true,
                'fieldConfig' => $params,
                'message' => 'Updated successfully'
            ];
        } catch (\Throwable $e) {
            $results[] = [
                'key'     => $item['key'] ?? null,
                'success' => false,
                'message' => 'Update operation failed: ' . $e->getMessage()
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
