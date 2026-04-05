<?php
declare(strict_types=1);

namespace App\Models\Office;
use App\System\NexaModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Importing Model untuk import data dari Excel/CSV
 */
class Importing extends NexaModel
{
    
    public function data(array $bulder): array {
      try {
        // Processing import data
        
        // Extract the components from $bulder
        // Expected format: [[$key => $name], $columns, $fieldConfig]
        if (count($bulder) < 3) {
            throw new \InvalidArgumentException('Insufficient parameters. Expected: [tableInfo, columns, fieldConfig]');
        }
        
        $tableInfo = $bulder[0]; // [$key => $name]
        $columns = $bulder[1];   // Form data including file data
        $fieldConfig = $bulder[2]; // File field configuration
        
        // Check if any field has importing=true
        $importingFields = $this->getImportingFields($fieldConfig);
        
        if (empty($importingFields)) {
            // No importing fields, just return original data
            return [
                'success' => true,
                'processedData' => $columns,
                'importedRecords' => 0
            ];
        }
        
        // Process importing files
        return $this->processImportFiles($columns, $importingFields, $tableInfo);

    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'query' => '',
            'response' => [],
            'importedRecords' => 0
        ];
    }
    }

    /**
     * Get fields that have importing=true
     */
    private function getImportingFields(array $fieldConfig): array {
        $importingFields = [];
        
        foreach ($fieldConfig as $field) {
            if (isset($field['importing']) && $field['importing'] === true) {
                $importingFields[] = $field;
            }
        }
        
        return $importingFields;
    }
    
    /**
     * Process importing files and return imported data
     */
    private function processImportFiles(array $columns, array $importingFields, array $tableInfo): array {
        $importedData = [];
        $totalImported = 0;
        
        foreach ($importingFields as $field) {
            $fieldName = $field['name'];
            
            // Check if this field has file data in columns
            if (!isset($columns[$fieldName])) {
                continue;
            }
            
            $fileData = $columns[$fieldName];
            
            // Validate file type
            if (!$this->validateImportFile($fileData, $field)) {
                throw new \Exception("Invalid file type for field {$fieldName}. Expected: {$field['accept']}");
            }
            
            // Extract file content and save temporarily
            $tempFilePath = $this->saveTemporaryFile($fileData);
            
            if (!$tempFilePath) {
                throw new \Exception("Failed to save temporary file for {$fieldName}");
            }
            
            try {
                // Parse Excel/CSV data
                $parsedData = $this->parseSpreadsheetFile($tempFilePath);
                
                // Insert batch data to database
                $this->insertBatchData($parsedData, $tableInfo);
                
                $importedData[$fieldName] = $parsedData;
                $totalImported += count($parsedData);
                
                // Remove file reference from columns since we've processed it
                unset($columns[$fieldName]);
                
            } finally {
                // Clean up temporary file
                if (file_exists($tempFilePath)) {
                    unlink($tempFilePath);
                }
            }
        }
        
        return [
            'success' => true,
            'processedData' => $columns, // Return other form data without file fields
            'importedData' => $importedData,
            'importedRecords' => $totalImported,
            'message' => "Successfully imported {$totalImported} records"
        ];
    }
    
    /**
     * Validate imported file type and size
     */
    private function validateImportFile($fileData, array $fieldConfig): bool {
        if (!is_array($fileData)) {
            return false;
        }
        
        // Get file name and type
        $fileName = $this->extractFileName($fileData);
        $fileSize = $this->extractFileSize($fileData);
        
        // Validate file extension
        $allowedExtensions = $this->parseAcceptPattern($fieldConfig['accept'] ?? '');
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return false;
        }
        
        // Validate file size
        if (isset($fieldConfig['maxSize'])) {
            $maxSizeBytes = $this->parseSize($fieldConfig['maxSize']);
            if ($fileSize > $maxSizeBytes) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Parse accept pattern like ".xls,.xlsx,.csv" to array of extensions
     */
    private function parseAcceptPattern(string $accept): array {
        $extensions = array_map('trim', explode(',', $accept));
        return array_map(function($ext) {
            return ltrim(strtolower($ext), '.');
        }, $extensions);
    }
    
    /**
     * Parse size string like "5MB" to bytes
     */
    private function parseSize(string $sizeStr): int {
        $sizeStr = strtoupper(trim($sizeStr));
        
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*(KB|MB|GB|TB|B)?$/', $sizeStr, $matches)) {
            return 0;
        }
        
        $size = (float)$matches[1];
        $unit = $matches[2] ?? 'B';
        
        $multipliers = [
            'B' => 1,
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024,
            'TB' => 1024 * 1024 * 1024 * 1024,
        ];
        
        return (int)($size * $multipliers[$unit]);
    }
    
    /**
     * Save file data to temporary location for processing
     */
    private function saveTemporaryFile($fileData): ?string {
        try {
            $fileName = $this->extractFileName($fileData);
            $fileContent = $this->extractFileContent($fileData);
            
            if (empty($fileContent)) {
                return null;
            }
            
            $tempDir = sys_get_temp_dir();
            $tempFileName = 'import_' . uniqid() . '_' . $fileName;
            $tempFilePath = $tempDir . DIRECTORY_SEPARATOR . $tempFileName;
            
            $bytesWritten = file_put_contents($tempFilePath, $fileContent);
            
            return $bytesWritten !== false ? $tempFilePath : null;
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Extract filename from file data
     */
    private function extractFileName(array $data): string {
        $nameFields = ['name', 'filename', 'fileName', 'file_name', 'originalName', 'original_name'];
        
        foreach ($nameFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return $data[$field];
            }
        }
        
        return 'import_file_' . uniqid() . '.xlsx';
    }
    
    /**
     * Extract file content from file data
     */
    private function extractFileContent(array $data): string {
        // Handle processed file data with 'content' property
        if (isset($data['content'])) {
            $content = $data['content'];
            
            // Check for binary array format from JavaScript
            if (is_array($content) && !empty($content) && is_numeric($content[0])) {
                return pack('C*', ...$content);
            }
            
            if (is_string($content) && !empty($content)) {
                return $content;
            }
        }
        
        // Handle File object with 'file' property
        if (isset($data['file'])) {
            if (is_array($data['file']) && isset($data['file']['content'])) {
                $content = $data['file']['content'];
                if (is_array($content) && !empty($content) && is_numeric($content[0])) {
                    return pack('C*', ...$content);
                }
            }
            
            if (is_string($data['file']) && !empty($data['file'])) {
                return $data['file'];
            }
        }
        
        return '';
    }
    
    /**
     * Extract file size from file data
     */
    private function extractFileSize(array $data): int {
        $sizeFields = ['size', 'fileSize', 'file_size', 'length', 'contentLength'];
        
        foreach ($sizeFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                return (int)$data[$field];
            }
        }
        
        // Fallback to content length if available
        $content = $this->extractFileContent($data);
        return strlen($content);
    }
    
    /**
     * Parse Excel/CSV file and return array of data
     */
    private function parseSpreadsheetFile(string $filePath): array {
        try {
            if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                throw new \Exception("PHPSpreadsheet library not found");
            }
            
            // Load with specific reader settings to preserve original values
            $reader = IOFactory::createReaderForFile($filePath);
            
            // Set reader options to preserve original cell values
            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(false); // Read formatting too
            }
            if (method_exists($reader, 'setReadEmptyCells')) {
                $reader->setReadEmptyCells(true);
            }
            
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Use toArray() but with specific options to preserve original values
            $rows = $worksheet->toArray(null, true, true, true);
            
            if (empty($rows)) {
                return [];
            }
            
            // Process rows to preserve original string values
            $data = [];
            foreach ($rows as $rowIndex => $row) {
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }
                
                $cleanRow = [];
                $hasData = false;
                
                foreach ($row as $colIndex => $value) {
                    // Get the original cell value as string to preserve leading zeros
                    $cell = $worksheet->getCell($colIndex . $rowIndex);
                    $originalValue = $this->getOriginalCellValue($cell);
                    
                    if ($this->isValidValue($originalValue)) {
                        $cleanRow[] = $originalValue;
                        $hasData = true;
                    } else {
                        $cleanRow[] = '';
                    }
                }
                
                if ($hasData) {
                    $data[] = array_values($cleanRow); // Convert to indexed array
                }
            }
            
            return $data;
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to parse spreadsheet file: " . $e->getMessage());
        }
    }
    
    /**
     * Get original cell value as string, preserving format and distinguishing dates from numeric strings
     */
    private function getOriginalCellValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): string {
        try {
            // Get the raw value first
            $rawValue = $cell->getValue();
            
            // If the value is null or empty, return empty string
            if ($rawValue === null) {
                return '';
            }
            
            // Get the cell's number format to understand how it was formatted
            $numberFormat = $cell->getStyle()->getNumberFormat()->getFormatCode();
            
            // Check if this is actually a date formatted cell
            $isDateFormatted = $this->isDateFormat($numberFormat);
            
            // Handle different types of values
            if ($isDateFormatted && is_numeric($rawValue)) {
                // This is a proper date cell - convert to readable date format
                try {
                    $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawValue);
                    return $dateValue->format('d-M-y'); // Format like "27-Apr-25"
                } catch (\Exception $e) {
                    // If date conversion fails, treat as string
                    return (string)$rawValue;
                }
            } elseif (is_numeric($rawValue) && !$isDateFormatted) {
                // This is a numeric value that should stay as string
                // Check if it needs leading zeros based on the original value pattern
                
                // If the number is in a range that suggests it had leading zeros
                if ($rawValue >= 1 && $rawValue <= 999999) {
                    // Try to preserve leading zeros for common patterns
                    return sprintf('%06d', (int)$rawValue); // 6-digit with leading zeros
                }
                
                return (string)$rawValue;
            } else {
                // For all other cases (text, formulas, etc.), return as string
                return (string)$rawValue;
            }
            
        } catch (\Exception $e) {
            // Fallback to basic string conversion
            return (string)$cell->getValue();
        }
    }
    
    /**
     * Check if number format is a date format
     */
    private function isDateFormat(string $format): bool {
        // Common date format patterns
        $datePatterns = [
            // Standard date formats
            'd/m/yyyy', 'dd/mm/yyyy', 'm/d/yyyy', 'mm/dd/yyyy',
            'd-m-yyyy', 'dd-mm-yyyy', 'm-d-yyyy', 'mm-dd-yyyy',
            'yyyy-mm-dd', 'yyyy/mm/dd',
            'd/m/yy', 'dd/mm/yy', 'm/d/yy', 'mm/dd/yy',
            'd-m-yy', 'dd-mm-yy', 'm-d-yy', 'mm-dd-yy',
            // Month name formats
            'dd-mmm-yy', 'd-mmm-yy', 'dd-mmm-yyyy', 'd-mmm-yyyy',
            'mmm-dd', 'mmm-d', 'mmmm-dd', 'mmmm-d',
            // Excel internal date format codes
            '[$-F800]', '[$-F400]', '[$-F200]', '[$-F100]',
            '[$-F080]', '[$-F040]', '[$-F020]', '[$-F010]'
        ];
        
        foreach ($datePatterns as $pattern) {
            if (stripos($format, $pattern) !== false) {
                return true;
            }
        }
        
        // Check for common date format patterns using regex
        if (preg_match('/[dmy]+[\/\-\.][dmy]+[\/\-\.][dmy]+/i', $format)) {
            return true;
        }
        
        // Check for month name patterns
        if (preg_match('/mmm|mmmm/i', $format)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Insert batch data to database
     */
    private function insertBatchData(array $data, array $tableInfo): array {
        try {
            if (empty($data)) {
                return ['success' => true, 'inserted' => 0];
            }
            
            // Extract table info
            $key = key($tableInfo);
            $tableName = reset($tableInfo);
            
            // Check if table exists in database and get correct table name
            $allTables = $this->showTables();
            // Get actual table name from database by index
            $actualTableName = $allTables[$key] ?? null;
            if (!$actualTableName) {
                throw new \Exception("Table not found at index {$key}. Available tables: " . implode(', ', $allTables));
            }
            
            // Get table field list using actual table name
            $tableFields = $this->showVariablesList([$key => $actualTableName]);
            
            // Extract field names (excluding auto fields)
            $allowedFields = [];
            $excludedFields = ['created_at', 'id', 'userid', 'row']; // Fields to exclude from import
            
            if (isset($tableFields[$actualTableName]) && isset($tableFields[$actualTableName]['variables'])) {
                foreach ($tableFields[$actualTableName]['variables'] as $fieldName) {
                    if (!empty($fieldName) && !in_array(strtolower($fieldName), array_map('strtolower', $excludedFields))) {
                        $allowedFields[] = $fieldName;
                    }
                }
            }
            // Ready for batch insert
            
            // Insert data in batches for better performance
            // Adjust batch size based on data volume for optimal performance
            $dataCount = count($data);
            $batchSize = $this->calculateOptimalBatchSize($dataCount);
            $totalInserted = 0;
            $errors = [];
            
            // Log large data imports for monitoring
            if ($dataCount > 10000) {
                error_log("LARGE IMPORT: Processing {$dataCount} records with batch size {$batchSize}");
            }
            
            for ($i = 0; $i < count($data); $i += $batchSize) {
                $batch = array_slice($data, $i, $batchSize);
                
                // Progress monitoring for large imports
                if ($dataCount > 5000 && ($i % ($batchSize * 10)) == 0) {
                    $progress = round(($i / $dataCount) * 100, 1);
                    error_log("IMPORT PROGRESS: {$progress}% ({$i}/{$dataCount} records processed)");
                }
                
                foreach ($batch as $recordIndex => $record) {
                    try {
                        // Map Excel data (indexed array) to database fields
                        $filteredRecord = [];
                        
                        // Map by position: Excel columns → Database fields
                        // Insert ALL fields from database structure with corresponding Excel data
                        foreach ($allowedFields as $index => $dbField) {
                            if (isset($record[$index])) {
                                // Ada data Excel di posisi ini
                                $value = $record[$index];
                                $cleanValue = trim($value ?? '');
                                
                                if ($this->isValidValue($cleanValue)) {
                                    $filteredRecord[$dbField] = $cleanValue;
                                } else {
                                    // Set empty untuk field yang tidak valid tapi tetap mapping
                                    $filteredRecord[$dbField] = '';
                                }
                            } else {
                                // Tidak ada data Excel di posisi ini, set empty
                                $filteredRecord[$dbField] = '';
                            }
                        }
                        
                        // Check if record has meaningful data (not just empty values)
                        $hasValidData = $this->hasValidData($filteredRecord, $allowedFields);
                        
                        if (!empty($filteredRecord) && $hasValidData) {
                            // Add userid automatically
                            $userId = $this->userid();
                            if ($userId) {
                                $filteredRecord['userid'] = $userId;
                            }
                            
                            // Insert ALL mapped fields (including empty ones for complete structure)
                            $this->Storage($actualTableName)->insert($filteredRecord);
                            $totalInserted++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Record " . ($recordIndex + 1) . ": " . $e->getMessage();
                    }
                }
            }
            
            return [
                'success' => true,
                'inserted' => $totalInserted,
                'total' => count($data),
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to insert batch data: " . $e->getMessage());
        }
    }
    
    /**
     * Check if record has valid/meaningful data
     */
    private function hasValidData(array $record, array $allowedFields = []): bool {
        if (empty($record)) {
            return false;
        }
        
        // Count non-empty fields - SEMUA FIELD DIPERHITUNGKAN
        $nonEmptyFields = 0;
        $totalCharacters = 0;
        
        foreach ($record as $field => $value) {
            $cleanValue = trim($value ?? '');
            
            // Validasi value yang tidak kosong
            if ($this->isValidValue($cleanValue)) {
                $nonEmptyFields++;
                $totalCharacters += strlen($cleanValue);
            }
        }
        
        // Record valid jika ada minimal 1 field dengan content
        // TIDAK ADA BATASAN ARTIFICIAL - semua data yang valid akan disimpan
        return $nonEmptyFields > 0 && $totalCharacters > 0;
    }
    
    /**
     * Check if a value is considered valid (not empty-like)
     */
    private function isValidValue(string $value): bool {
        if (empty($value)) {
            return false;
        }
        
        // List of values considered as empty
        $emptyValues = [
            '', 'NULL', 'null', 'Null', 'NONE', 'none',
            '-', '--', '---', '----',
            'N/A', 'n/a', 'NA', 'na', 'NO DATA', 'no data',
            '0', '0.0', '0,0', '0.00', '0,00',
            ' ', '  ', '   ', '    ',
            '1900-01-01', '1970-01-01', '0000-00-00',
            '#REF!', '#N/A', '#NULL!', '#VALUE!', '#ERROR!'
        ];
        
        $trimmedValue = trim($value);
        
        // Check against empty values list
        if (in_array($trimmedValue, $emptyValues)) {
            return false;
        }
        
        // Check if it's only whitespace or special characters
        if (preg_match('/^[\s\-_.,;:]*$/', $trimmedValue)) {
            return false;
        }
        
        // Must have at least 1 character length
        return strlen($trimmedValue) > 0;
    }
    
    /**
     * Determine important fields based on common field patterns
     */
    private function getImportantFields(array $allowedFields): array {
        // SEMUA FIELDS ADALAH PENTING! Tidak ada batasan static
        // Return semua allowedFields yang tersedia dari database
        return $allowedFields;
    }
    
    /**
     * Calculate optimal batch size based on data volume
     */
    private function calculateOptimalBatchSize(int $dataCount): int {
        // Optimize batch size for different data volumes
        if ($dataCount <= 1000) {
            return 50;  // Small datasets - smaller batches for quick processing
        } elseif ($dataCount <= 10000) {
            return 100; // Medium datasets - standard batch size
        } elseif ($dataCount <= 50000) {
            return 200; // Large datasets - bigger batches for efficiency
        } elseif ($dataCount <= 100000) {
            return 500; // Very large datasets - optimize for memory and speed
        } else {
            return 1000; // Massive datasets (100k+) - maximum efficiency
        }
    }

}
