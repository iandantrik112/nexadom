<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use App\System\NexaController;

/**
 * Text Controller untuk API endpoints
 */
class DatasetsController extends NexaController
{
    /**
     * Text endpoint
     */
    public function index(): array
    {


            $Datasets = $this->Storage('nexa_office')
            ->select(['title AS instansi','data_value'])
            ->where('to_id', 642)
            ->get();
        
        // Extract hanya fileSettings dari setiap data_value
        $fileSettingsList = [];
        $metadata = null; // Variabel untuk menyimpan metadata umum
        $totalFileCount = 0; // Hitung jumlah file dengan categori "Terbuka"
        $totalSize = 0; // Total size dari semua file
        
        foreach ($Datasets as $dataset) {
            if (isset($dataset['data_value']) && is_array($dataset['data_value'])) {
                $dataValue = is_string($dataset['data_value']) ? json_decode($dataset['data_value'], true) : $dataset['data_value'];
                
                // Ambil metadata umum dari data_value (hanya sekali, data umum)
                if (!$metadata && isset($dataValue['metadata'])) {
                    $originalMetadata = $dataValue['metadata'];
                    
                    // Ambil instansi dari dataset
                    $instansi = $dataset['instansi'] ?? null;
                    
                    // Buat metadata baru dengan field yang diinginkan saja (file akan diupdate nanti)
                    $metadata = [
                        'instansi' => $instansi,
                        'file' => 0, // Akan diupdate setelah menghitung file yang terfilter
                        'size' => null, // Akan diupdate setelah menghitung total size
                        'type' => $originalMetadata['type'] ?? null,
                        'modified' => $originalMetadata['modified'] ?? null,
                        'createdAt' => $originalMetadata['createdAt'] ?? null,
                        'updatedAt' => $originalMetadata['updatedAt'] ?? null,
                    ];
                }
                
                if (isset($dataValue['fileSettings']) && is_array($dataValue['fileSettings'])) {
                    $instansi = $dataset['instansi'] ?? null;
                    
                    foreach ($dataValue['fileSettings'] as $fileSetting) {
                        // Hapus field 'nama' dari setiap fileSetting
                        if (isset($fileSetting['nama'])) {
                            unset($fileSetting['nama']);
                        }
                        
                        // Tambahkan instansi
                        if ($instansi) {
                            $fileSetting['instansi'] = $instansi;
                        }
                        
                        // Ambil type file dari ekstensi title (prioritas utama)
                        $fileType = null;
                        if (isset($fileSetting['title']) && !empty($fileSetting['title'])) {
                            $pathInfo = pathinfo($fileSetting['title']);
                            if (isset($pathInfo['extension'])) {
                                $fileType = strtolower($pathInfo['extension']);
                            }
                        }
                        
                        // Jika tidak ditemukan dari title, coba ambil dari fileContents sebagai fallback
                        if (!$fileType && isset($dataValue['fileContents']) && is_array($dataValue['fileContents'])) {
                            foreach ($dataValue['fileContents'] as $fileContent) {
                                // Cek berdasarkan fileId yang cocok dengan fileSetting['id']
                                if (isset($fileContent['fileId']) && $fileContent['fileId'] === $fileSetting['id']) {
                                    // Ambil dari existingContent.fileType dan ekstrak ekstensi jika MIME type
                                    if (isset($fileContent['existingContent']['fileType'])) {
                                        $mimeType = $fileContent['existingContent']['fileType'];
                                        // Jika MIME type, ekstrak ekstensi dari fileName
                                        if (isset($fileContent['existingContent']['fileName'])) {
                                            $pathInfo = pathinfo($fileContent['existingContent']['fileName']);
                                            if (isset($pathInfo['extension'])) {
                                                $fileType = strtolower($pathInfo['extension']);
                                                break;
                                            }
                                        }
                                    }
                                    // Ambil dari type di level atas jika bukan 'file'
                                    if (!$fileType && isset($fileContent['type']) && $fileContent['type'] !== 'file') {
                                        $fileType = $fileContent['type'];
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // Tambahkan type file
                        if ($fileType) {
                            $fileSetting['type'] = $fileType;
                        }
                        
                        // Ambil size dari bucketsStore atau existingContent untuk setiap file
                        $fileSizeInBytes = null;
                        $appData = null;
                        
                        // Prioritas 1: Cari di bucketsStore
                        if (isset($dataValue['bucketsStore']) && is_array($dataValue['bucketsStore'])) {
                            foreach ($dataValue['bucketsStore'] as $bucket) {
                                if (isset($bucket['id']) && $bucket['id'] === $fileSetting['id']) {
                                    if (isset($bucket['size']) && is_numeric($bucket['size'])) {
                                        $fileSizeInBytes = (int)$bucket['size'];
                                    }
                                    // Simpan appData untuk estimasi size jika file status: false
                                    if (isset($bucket['appData']) && is_array($bucket['appData'])) {
                                        $appData = $bucket['appData'];
                                    }
                                    break;
                                }
                            }
                        }
                        
                        // Prioritas 2: Cari di fileContents -> existingContent (jika belum ditemukan)
                        if ($fileSizeInBytes === null && isset($dataValue['fileContents']) && is_array($dataValue['fileContents'])) {
                            foreach ($dataValue['fileContents'] as $fileContent) {
                                // Cek berdasarkan fileId yang cocok dengan fileSetting['id']
                                if (isset($fileContent['fileId']) && $fileContent['fileId'] === $fileSetting['id']) {
                                    if (isset($fileContent['existingContent']['size']) && is_numeric($fileContent['existingContent']['size'])) {
                                        $fileSizeInBytes = (int)$fileContent['existingContent']['size'];
                                    }
                                    // Ambil appData jika ada
                                    if ($appData === null && isset($fileContent['existingContent']['appData']) && is_array($fileContent['existingContent']['appData'])) {
                                        $appData = $fileContent['existingContent']['appData'];
                                    }
                                    break;
                                }
                            }
                        }
                        
                        // Jika file adalah xlsx dengan status: false dan memiliki appData, hitung estimasi size
                        $isXlsx = ($fileType === 'xlsx' || (isset($fileSetting['title']) && stripos($fileSetting['title'], '.xlsx') !== false));
                        $fileStatus = $fileSetting['status'] ?? null;
                        
                        if (($fileSizeInBytes === null || $fileSizeInBytes === 0) && $isXlsx && $fileStatus === false && $appData !== null) {
                            // Estimasi size berdasarkan jumlah data di appData
                            $fileSizeInBytes = $this->estimateExcelSize($appData);
                        }
                        
                        // Konversi size ke format yang readable
                        if ($fileSizeInBytes !== null && $fileSizeInBytes >= 0) {
                            if ($fileSizeInBytes < 1024) {
                                $fileSetting['size'] = $fileSizeInBytes . ' B';
                            } elseif ($fileSizeInBytes < 1024 * 1024) {
                                $fileSetting['size'] = round($fileSizeInBytes / 1024, 2) . ' KB';
                            } else {
                                $fileSetting['size'] = round($fileSizeInBytes / (1024 * 1024), 2) . ' MB';
                            }
                        } else {
                            // Default jika size tidak ditemukan
                            $fileSetting['size'] = '0 B';
                            $fileSizeInBytes = 0;
                        }
                        
                        // Buat variabel link dan akama yang mengarah ke method berikutnya
                        // Gunakan fileName atau title sebagai parameter untuk download
                        $fileIdentifier = $fileSetting['title'] ?? $fileSetting['fileName'] ?? $fileSetting['id'] ?? '';
                        
                        // Link mengarah ke method dowloade untuk download file
                        $fileSetting['link'] = $this->url('/api/datasets/dowloade/file/' . urlencode($fileSetting['id'] ?? ''));
                        
                        // Metadata mengarah ke method metadata untuk mendapatkan struktur metadata
                        $fileSetting['metadata'] = $this->url('/api/datasets/metadata/file/' . urlencode($fileSetting['id'] ?? ''));
                        
                        // Hanya tambahkan jika categori adalah "Terbuka"
                        if (isset($fileSetting['categori']) && $fileSetting['categori'] === 'Terbuka') {
                            $fileSettingsList[] = $fileSetting;
                            $totalFileCount++; // Hitung jumlah file
                            
                            // Tambahkan size ke total (dalam bytes)
                            if ($fileSizeInBytes !== null) {
                                $totalSize += $fileSizeInBytes;
                            }
                        }
                    }
                }
            }
        }
        
        // Update metadata dengan jumlah file dan total size yang benar
        if ($metadata) {
            $metadata['file'] = $totalFileCount;
            
            // Konversi total size ke format yang readable
            if ($totalSize > 0) {
                if ($totalSize < 1024) {
                    $metadata['size'] = $totalSize . ' B';
                } elseif ($totalSize < 1024 * 1024) {
                    $metadata['size'] = round($totalSize / 1024, 2) . ' KB';
                } else {
                    $metadata['size'] = round($totalSize / (1024 * 1024), 2) . ' MB';
                }
            } else {
                // Jika total size = 0, tetap tampilkan
                $metadata['size'] = '0 B';
            }
        }
        
        return [
            'status' => 'success',
            'server_time' => date('Y-m-d H:i:s'),
            'metadata' => $metadata ?? null,
            'data' => $fileSettingsList
        ];
    }
    public function dowloade($params = []): void
    {
        // Ambil fileName dari URL path atau params
        $fileIdentifier = null;
        
        // Coba ambil dari slug (URL path seperti /api/datasets/dowloade/file/petani.xlsx)
        // slug 3 = 'file', slug 4 = 'petani.xlsx'
        $slug3 = $this->getSlug(3, '');
        $slug4 = $this->getSlug(4, '');
        
        if ($slug3 === 'file' && !empty($slug4)) {
            $fileIdentifier = $slug4;
        } elseif (!empty($slug3) && $slug3 !== 'file') {
            // Jika slug3 bukan 'file', mungkin langsung fileName
            $fileIdentifier = $slug3;
        } elseif (!empty($slug4)) {
            $fileIdentifier = $slug4;
        }
        
        // Coba ambil dari params array
        if (!$fileIdentifier && is_array($params)) {
            // Jika params adalah array dengan format ['file', 'petani.xlsx']
            if (isset($params[0]) && $params[0] === 'file' && isset($params[1])) {
                $fileIdentifier = $params[1];
            } elseif (isset($params['file'])) {
                $fileIdentifier = $params['file'];
            } elseif (isset($params[0])) {
                // Jika hanya ada satu elemen, anggap itu adalah fileName
                $fileIdentifier = $params[0];
            }
        }
        
        // Fallback ke query string
        if (!$fileIdentifier) {
            $fileIdentifier = $_GET['file'] ?? null;
        }
        
        // Fallback ke REQUEST_URI parsing
        if (!$fileIdentifier) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            // Parse URL seperti /api/datasets/dowloade/file/petani.xlsx
            $uriParts = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));
            $dowloadeIndex = array_search('dowloade', $uriParts);
            if ($dowloadeIndex !== false && isset($uriParts[$dowloadeIndex + 2])) {
                // Jika ada 'file' setelah 'dowloade', ambil yang setelahnya
                if ($uriParts[$dowloadeIndex + 1] === 'file') {
                    $fileIdentifier = $uriParts[$dowloadeIndex + 2];
                } else {
                    // Jika tidak ada 'file', ambil langsung setelah 'dowloade'
                    $fileIdentifier = $uriParts[$dowloadeIndex + 1];
                }
            }
        }
        
        if (!$fileIdentifier) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'File name is required'
            ]);
            exit;
        }
        
        // Ambil data dari storage
        $Datasets = $this->Storage('nexa_office')
            ->select(['data_value'])
            ->where('to_id', 642)
            ->get();
        
        $fileContent = null;
        $fileName = null;
        $fileType = null;
        $fileStatus = null;
        $appData = null;
        
        // Cari content dari bucketsStore berdasarkan fileName atau title
        foreach ($Datasets as $dataset) {
            if (isset($dataset['data_value']) && is_array($dataset['data_value'])) {
                $dataValue = is_string($dataset['data_value']) ? json_decode($dataset['data_value'], true) : $dataset['data_value'];
                
                // Cek di bucketsStore
                if (isset($dataValue['bucketsStore']) && is_array($dataValue['bucketsStore'])) {
                    foreach ($dataValue['bucketsStore'] as $bucket) {
                        // Cek berdasarkan fileName atau id
                        if ((isset($bucket['fileName']) && $bucket['fileName'] === $fileIdentifier) ||
                            (isset($bucket['id']) && $bucket['id'] === $fileIdentifier)) {
                            $fileContent = $bucket['content'] ?? null;
                            $fileName = $bucket['fileName'] ?? 'download';
                            $fileType = $bucket['fileType'] ?? 'application/octet-stream';
                            $appData = $bucket['appData'] ?? null;
                            
                            // Cari status dari fileSettings
                            if (isset($dataValue['fileSettings']) && is_array($dataValue['fileSettings'])) {
                                foreach ($dataValue['fileSettings'] as $fileSetting) {
                                    if (isset($fileSetting['id']) && $fileSetting['id'] === $bucket['id']) {
                                        $fileStatus = $fileSetting['status'] ?? null;
                                        break;
                                    }
                                }
                            }
                            break 2; // Keluar dari kedua loop
                        }
                    }
                }
                
                // Jika tidak ditemukan di bucketsStore, cek di fileSettings
                if (!$fileContent && !$appData && isset($dataValue['fileSettings']) && is_array($dataValue['fileSettings'])) {
                    foreach ($dataValue['fileSettings'] as $fileSetting) {
                        if ((isset($fileSetting['title']) && $fileSetting['title'] === $fileIdentifier) ||
                            (isset($fileSetting['id']) && $fileSetting['id'] === $fileIdentifier)) {
                            // Cari di bucketsStore berdasarkan id dari fileSettings
                            $fileSettingId = $fileSetting['id'] ?? null;
                            $fileStatus = $fileSetting['status'] ?? null;
                            
                            if ($fileSettingId && isset($dataValue['bucketsStore']) && is_array($dataValue['bucketsStore'])) {
                                foreach ($dataValue['bucketsStore'] as $bucket) {
                                    if (isset($bucket['id']) && $bucket['id'] === $fileSettingId) {
                                        $fileContent = $bucket['content'] ?? null;
                                        $fileName = $bucket['fileName'] ?? $fileSetting['title'] ?? 'download';
                                        $fileType = $bucket['fileType'] ?? 'application/octet-stream';
                                        $appData = $bucket['appData'] ?? null;
                                        break 3; // Keluar dari semua loop
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Cek apakah ini file xlsx dengan status false dan memiliki appData
        $isXlsx = (stripos($fileName, '.xlsx') !== false || $fileType === 'xlsx');
        
        if ($isXlsx && $fileStatus === false && $appData !== null) {
            // Generate Excel dari appData
            $fileContent = $this->generateExcelFromAppData($appData, $fileName);
            $fileType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } elseif (!$fileContent) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'File not found'
            ]);
            exit;
        } else {
            // Decode content jika encoded (base64)
            $decodedContent = base64_decode($fileContent, true);
            if ($decodedContent === false) {
                // Jika bukan base64, gunakan content asli
                $decodedContent = $fileContent;
            }
            $fileContent = $decodedContent;
        }
        
        // Set header untuk download
        header('Content-Type: ' . $fileType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($fileContent));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Output file content
        echo $fileContent;
        exit;
    }
    
    /**
     * Generate Excel file dari appData secara dinamis
     */
    private function generateExcelFromAppData(array $appData, string $fileName): string
    {
        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        
        // Hapus sheet default
        $spreadsheet->removeSheetByIndex(0);
        
        // Cek apakah appData memiliki struktur sheets
        if (!isset($appData['sheets']) || !is_array($appData['sheets']) || empty($appData['sheets'])) {
            // Jika tidak ada sheets, buat sheet kosong
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Sheet1');
            $spreadsheet->addSheet($sheet);
        } else {
            // Loop melalui setiap sheet di appData
            $sheetIndex = 0;
            foreach ($appData['sheets'] as $sheetId => $sheetData) {
                // Ambil nama sheet secara dinamis
                $sheetName = isset($sheetData['name']) && !empty($sheetData['name']) 
                    ? $sheetData['name'] 
                    : 'Sheet' . ($sheetIndex + 1);
                
                // Buat worksheet baru
                $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);
                $spreadsheet->addSheet($worksheet, $sheetIndex);
                
                // Cek apakah ada data di sheet ini
                if (isset($sheetData['data']) && is_array($sheetData['data']) && !empty($sheetData['data'])) {
                    // Loop melalui data dan masukkan ke cell
                    foreach ($sheetData['data'] as $cellRef => $cellValue) {
                        // cellRef format: "0-0", "0-1", "1-0", dll (row-col)
                        $coords = explode('-', $cellRef);
                        if (count($coords) === 2 && is_numeric($coords[0]) && is_numeric($coords[1])) {
                            $row = (int)$coords[0] + 1; // Excel row dimulai dari 1
                            $col = (int)$coords[1] + 1; // Excel col dimulai dari 1
                            
                            // Konversi kolom ke huruf (A, B, C, ...)
                            $colLetter = Coordinate::stringFromColumnIndex($col);
                            $cellAddress = $colLetter . $row;
                            
                            // Set nilai cell - PhpSpreadsheet akan otomatis deteksi tipe data
                            $worksheet->setCellValue($cellAddress, $cellValue);
                        }
                    }
                }
                
                // Terapkan column widths jika ada dan tidak kosong
                if (isset($sheetData['columnWidths']) && is_array($sheetData['columnWidths']) && !empty($sheetData['columnWidths'])) {
                    foreach ($sheetData['columnWidths'] as $colIndex => $width) {
                        if (is_numeric($colIndex) && is_numeric($width) && $width > 0) {
                            $colLetter = Coordinate::stringFromColumnIndex((int)$colIndex + 1);
                            // Konversi pixel ke character width (approx 7 pixels per character)
                            $worksheet->getColumnDimension($colLetter)->setWidth($width / 7);
                        }
                    }
                }
                
                // Terapkan row heights jika ada dan tidak kosong
                if (isset($sheetData['rowHeights']) && is_array($sheetData['rowHeights']) && !empty($sheetData['rowHeights'])) {
                    foreach ($sheetData['rowHeights'] as $rowIndex => $height) {
                        if (is_numeric($rowIndex) && is_numeric($height) && $height > 0) {
                            $worksheet->getRowDimension((int)$rowIndex + 1)->setRowHeight($height);
                        }
                    }
                }
                
                // Terapkan merged cells jika ada dan tidak kosong
                if (isset($sheetData['mergedCells']) && is_array($sheetData['mergedCells']) && !empty($sheetData['mergedCells'])) {
                    foreach ($sheetData['mergedCells'] as $mergedRange) {
                        if (!empty($mergedRange)) {
                            try {
                                $worksheet->mergeCells($mergedRange);
                            } catch (\Exception $e) {
                                // Skip jika merge gagal (range tidak valid)
                                continue;
                            }
                        }
                    }
                }
                
                // Terapkan settings/styles jika ada dan tidak kosong
                if (isset($sheetData['settings']) && is_array($sheetData['settings']) && !empty($sheetData['settings'])) {
                    foreach ($sheetData['settings'] as $setting) {
                        // Handle berbagai jenis settings secara dinamis
                        if (isset($setting['type']) && isset($setting['range'])) {
                            try {
                                $range = $setting['range'];
                                switch ($setting['type']) {
                                    case 'bold':
                                        if (isset($setting['value'])) {
                                            $worksheet->getStyle($range)->getFont()->setBold((bool)$setting['value']);
                                        }
                                        break;
                                    case 'italic':
                                        if (isset($setting['value'])) {
                                            $worksheet->getStyle($range)->getFont()->setItalic((bool)$setting['value']);
                                        }
                                        break;
                                    case 'fontSize':
                                        if (isset($setting['value']) && is_numeric($setting['value'])) {
                                            $worksheet->getStyle($range)->getFont()->setSize((float)$setting['value']);
                                        }
                                        break;
                                    case 'fontColor':
                                        if (isset($setting['value'])) {
                                            $worksheet->getStyle($range)->getFont()->getColor()->setARGB($setting['value']);
                                        }
                                        break;
                                    case 'backgroundColor':
                                        if (isset($setting['value'])) {
                                            $worksheet->getStyle($range)->getFill()
                                                ->setFillType(Fill::FILL_SOLID)
                                                ->getStartColor()->setARGB($setting['value']);
                                        }
                                        break;
                                    case 'alignment':
                                        if (isset($setting['horizontal'])) {
                                            $worksheet->getStyle($range)->getAlignment()->setHorizontal($setting['horizontal']);
                                        }
                                        if (isset($setting['vertical'])) {
                                            $worksheet->getStyle($range)->getAlignment()->setVertical($setting['vertical']);
                                        }
                                        break;
                                }
                            } catch (\Exception $e) {
                                // Skip jika setting gagal
                                continue;
                            }
                        }
                    }
                }
                
                $sheetIndex++;
            }
            
            // Set active sheet ke currentSheetId jika ada
            if (isset($appData['currentSheetId']) && !empty($appData['currentSheetId'])) {
                $currentSheetIndex = 0;
                foreach ($appData['sheets'] as $sheetId => $sheetData) {
                    if ($sheetId === $appData['currentSheetId']) {
                        try {
                            $spreadsheet->setActiveSheetIndex($currentSheetIndex);
                        } catch (\Exception $e) {
                            // Fallback ke sheet pertama jika gagal
                            $spreadsheet->setActiveSheetIndex(0);
                        }
                        break;
                    }
                    $currentSheetIndex++;
                }
            } else {
                // Set sheet pertama sebagai active
                $spreadsheet->setActiveSheetIndex(0);
            }
        }
        
        // Generate file Excel ke string
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $excelContent = ob_get_clean();
        
        return $excelContent;
    }
    
    /**
     * Estimasi ukuran file Excel dari appData
     */
    private function estimateExcelSize(array $appData): int
    {
        // Base size untuk file Excel kosong (minimal structure)
        $baseSize = 5000; // ~5KB untuk struktur dasar Excel
        
        $totalCells = 0;
        $avgCellSize = 50; // Rata-rata 50 bytes per cell dengan data
        
        // Hitung jumlah cell yang terisi dari semua sheet
        if (isset($appData['sheets']) && is_array($appData['sheets'])) {
            foreach ($appData['sheets'] as $sheetData) {
                if (isset($sheetData['data']) && is_array($sheetData['data'])) {
                    $totalCells += count($sheetData['data']);
                    
                    // Tambah estimasi untuk merged cells, column widths, row heights
                    if (isset($sheetData['mergedCells']) && is_array($sheetData['mergedCells'])) {
                        $baseSize += count($sheetData['mergedCells']) * 100;
                    }
                    if (isset($sheetData['columnWidths']) && is_array($sheetData['columnWidths'])) {
                        $baseSize += count($sheetData['columnWidths']) * 50;
                    }
                    if (isset($sheetData['rowHeights']) && is_array($sheetData['rowHeights'])) {
                        $baseSize += count($sheetData['rowHeights']) * 50;
                    }
                    if (isset($sheetData['settings']) && is_array($sheetData['settings'])) {
                        $baseSize += count($sheetData['settings']) * 100;
                    }
                }
            }
        }
        
        // Total estimasi: base size + (jumlah cell × rata-rata size per cell)
        $estimatedSize = $baseSize + ($totalCells * $avgCellSize);
        
        // Minimal 1KB jika ada data
        if ($estimatedSize < 1024 && $totalCells > 0) {
            $estimatedSize = 1024;
        }
        
        return (int)$estimatedSize;
    }
    
    /**
     * Metadata endpoint untuk mengambil struktur metadata
     */
    public function metadata($params = []): array
    {
        // Ambil file ID dari URL path atau params
        $fileId = null;
        
        // Coba ambil dari slug (URL path seperti /api/datasets/metadata/file/file_ruogrs)
        $slug3 = $this->getSlug(3, '');
        $slug4 = $this->getSlug(4, '');
        
        if ($slug3 === 'file' && !empty($slug4)) {
            $fileId = $slug4;
        } elseif (!empty($slug3) && $slug3 !== 'file') {
            $fileId = $slug3;
        } elseif (!empty($slug4)) {
            $fileId = $slug4;
        }
        
        // Coba ambil dari params array
        if (!$fileId && is_array($params)) {
            if (isset($params[0]) && $params[0] === 'file' && isset($params[1])) {
                $fileId = $params[1];
            } elseif (isset($params['file'])) {
                $fileId = $params['file'];
            } elseif (isset($params[0])) {
                $fileId = $params[0];
            }
        }
        
        // Fallback ke query string
        if (!$fileId) {
            $fileId = $_GET['file'] ?? $params['id'] ?? null;
        }
        
        // Fallback ke REQUEST_URI parsing
        if (!$fileId) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $uriParts = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));
            $metadataIndex = array_search('metadata', $uriParts);
            if ($metadataIndex !== false && isset($uriParts[$metadataIndex + 2])) {
                if ($uriParts[$metadataIndex + 1] === 'file') {
                    $fileId = $uriParts[$metadataIndex + 2];
                } else {
                    $fileId = $uriParts[$metadataIndex + 1];
                }
            }
        }
        
        if (!$fileId) {
            return [
                'status' => 'error',
                'message' => 'File ID is required',
                'timestamp' => time()
            ];
        }
        
        // Ambil data dari storage
        $Datasets = $this->Storage('nexa_office')
            ->select(['data_value'])
            ->where('to_id', 642)
            ->get();
        
        $metadata = null;
        $existingContent = null;
        $fileContents = null;
        $foundDataValue = null;
        
        // Cari metadata dari data_value berdasarkan file ID
        foreach ($Datasets as $dataset) {
            if (isset($dataset['data_value']) && is_array($dataset['data_value'])) {
                $dataValue = is_string($dataset['data_value']) ? json_decode($dataset['data_value'], true) : $dataset['data_value'];
                
                // Cek apakah file ID cocok dengan fileSettings atau bucketsStore
                $fileFound = false;
                
                // Cek di fileSettings dan ambil existingContent
                if (isset($dataValue['fileSettings']) && is_array($dataValue['fileSettings'])) {
                    foreach ($dataValue['fileSettings'] as $fileSetting) {
                        if (isset($fileSetting['id']) && $fileSetting['id'] === $fileId) {
                            $fileFound = true;
                            break;
                        }
                    }
                }
                
                // Cek di bucketsStore
                if (!$fileFound && isset($dataValue['bucketsStore']) && is_array($dataValue['bucketsStore'])) {
                    foreach ($dataValue['bucketsStore'] as $bucket) {
                        if (isset($bucket['id']) && $bucket['id'] === $fileId) {
                            $fileFound = true;
                            break;
                        }
                    }
                }
                
                // Jika file ditemukan, ambil metadata dan existingContent
                if ($fileFound) {
                    $foundDataValue = $dataValue; // Simpan untuk digunakan nanti
                    
                    if (isset($dataValue['metadata'])) {
                        $metadata = $dataValue['metadata'];
                    }
                    
                    // Cari existingContent dari fileContents berdasarkan fileId
                    if (isset($dataValue['fileContents']) && is_array($dataValue['fileContents'])) {
                        foreach ($dataValue['fileContents'] as $fileContent) {
                            if (isset($fileContent['fileId']) && $fileContent['fileId'] === $fileId) {
                                if (isset($fileContent['existingContent'])) {
                                    $existingContent = $fileContent['existingContent'];
                                }
                                break;
                            }
                        }
                    }
                    
                    // Simpan fileContents untuk menghitung jumlah data
                    if (isset($dataValue['fileContents'])) {
                        $fileContents = $dataValue['fileContents'];
                    }
                    
                    break;
                }
            }
        }
        
        if (!$metadata) {
            return [
                'status' => 'error',
                'message' => 'Metadata not found for the specified file ID',
                'timestamp' => time()
            ];
        }
        
        // Update size dari existingContent jika ada
        if ($existingContent && isset($existingContent['size'])) {
            $sizeInBytes = $existingContent['size'];
            // Konversi ke format yang lebih readable
            if ($sizeInBytes < 1024) {
                $metadata['size'] = $sizeInBytes . ' B';
            } elseif ($sizeInBytes < 1024 * 1024) {
                $metadata['size'] = round($sizeInBytes / 1024, 2) . ' KB';
            } else {
                $metadata['size'] = round($sizeInBytes / (1024 * 1024), 2) . ' MB';
            }
        }
        
        // Hitung jumlah data di dalam folder jika type adalah folder
        if (isset($metadata['type']) && $metadata['type'] === 'folder' && is_array($fileContents) && $foundDataValue) {
            $fileCount = count($fileContents);
            $folderCount = 0;
            
            // Hitung folder dari folder.data jika ada
            if (isset($foundDataValue['folder']['data']) && is_array($foundDataValue['folder']['data'])) {
                foreach ($foundDataValue['folder']['data'] as $item) {
                    if (isset($item['type']) && $item['type'] === 'folder') {
                        $folderCount++;
                    }
                }
            }
            
            // Update metadata dengan jumlah file dan folder
            $metadata['file'] = $fileCount;
            $metadata['folder'] = $folderCount;
        }
        
        return [
            'status' => 'success',
            'message' => 'Metadata retrieved successfully',
            'timestamp' => time(),
            'data' => $metadata
        ];
    }
    
}

