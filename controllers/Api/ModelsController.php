<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * Test Controller untuk API endpoints
 */
class ModelsController extends NexaController
{
    /**
     * Main endpoint untuk NexaModels API
     * Handle SQL queries dari NexaModels (sama seperti UserController)
     */
    public function index(): array
    {
        // Get request data (bisa dari GET atau POST)
        $requestData = $this->getRequestData();
        
        // Check if this is a NexaModels API call with SQL query
        // Format: { sql: "encrypted", table: "encrypted", bindings: [], type: "select" }
        if (isset($requestData['sql']) && isset($requestData['type'])) {
            // Process SQL query using NexaBig (sama seperti UserController)
            // NexaBig akan:
            // 1. Decrypt SQL: $this->Encrypt->decryptJson($requestData['sql'])
            // 2. Decrypt table: $this->Encrypt->decryptJson($requestData['table'])
            // 3. Execute query menggunakan NexaModel
            // 4. Return hasil query
            return $this->NexaBig($requestData);
        }
        
        // Default response jika bukan SQL query
        return [
            'status' => 'success',
            'message' => 'Models API endpoint working!',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s'),
            'data' => [
                'endpoint' => 'models',
                'version' => '1.0.0',
                'note' => 'Send SQL query with encrypted sql, table, bindings, and type fields'
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
     * Handle SQL queries dari NexaModels (sama seperti UserController)
     */
    public function created($data = [], $params = []): array
    {
        // Handle SQL queries (sama seperti UserController::created)
        // Check if this is a NexaModels API call with SQL query
        if (isset($data['sql']) && isset($data['type'])) {
            // Process SQL query using NexaBig (sama seperti UserController)
            return $this->NexaBig($data);
        }
        
        // Default response untuk POST tanpa SQL query
        return [
            'status' => 'success',
            'message' => 'Data created successfully',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
    }

    /**
     * Red endpoint for PUT requests
     * Handle SQL queries dari NexaModels (sama seperti UserController)
     */
    public function red($data = [], $params = []): array
    {
        // Handle SQL queries (sama seperti UserController::red)
        // Check if this is a NexaModels API call with SQL query
        if (isset($data['sql']) && isset($data['type'])) {
            // Process SQL query using NexaBig (sama seperti UserController)
            return $this->NexaBig($data);
        }
        
        // Default response untuk PUT tanpa SQL query
        return [
            'status' => 'success',
            'message' => 'Data updated successfully (red)',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
    }
    /**
     * Updated endpoint for PATCH requests
     * Handle SQL queries dari NexaModels (sama seperti UserController)
     */
    public function updated($data = [], $params = []): array
    {
        // Handle SQL queries (sama seperti UserController::updated)
        // Check if this is a NexaModels API call with SQL query
        if (isset($data['sql']) && isset($data['type'])) {
            // Process SQL query using NexaBig (sama seperti UserController)
            return $this->NexaBig($data);
        }
        
        // Default response untuk PATCH tanpa SQL query
        return [
            'status' => 'success',
            'message' => 'Data updated successfully',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
    }
    
    /**
     * Deleted endpoint for DELETE requests
     * Handle SQL queries dari NexaModels (sama seperti UserController)
     */
    public function deleted($data = [], $params = []): array
    {
        // Handle SQL queries (sama seperti UserController::deleted)
        // Check if this is a NexaModels API call with SQL query
        if (isset($data['sql']) && isset($data['type'])) {
            // Process SQL query using NexaBig (sama seperti UserController)
            return $this->NexaBig($data);
        }
        
        // Default response untuk DELETE tanpa SQL query
        return [
            'status' => 'success',
            'message' => 'Data deleted successfully',
            'timestamp' => time(),
            'data' => $data,
            'params' => $params
        ];
    }
} 