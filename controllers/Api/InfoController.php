<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * RESTful Info API Controller
 * Handles system information endpoints
 */
class InfoController extends NexaController
{
    /**
     * GET /api/info
     * List all available information
     */
    public function index(): array
    {
        return [
            'status' => 'success',
            'data' => [
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    // 'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'uptime' => $this->getServerUptime(),
                    'memory' => $this->getMemoryUsage()
                ]
            ]
        ];
    }
    
    /**
     * GET /api/info/health
     * Health check endpoint
     */
    public function updated(array $data = []): array
    {
        // Validate ID
        // if (empty($data['id'])) {
        //     return [
        //         'status' => 'error',
        //         'code' => 400,
        //         'message' => 'ID is required for updates'
        //     ];
        // }

        return [
            'status' => 'success',
            'message' => 'Info updated successfully',
            'data' => $data
        ];
    }
    public function cek(array $data = []): array
    {
        // Validate ID
        // if (empty($data['id'])) {
        //     return [
        //         'status' => 'error',
        //         'code' => 400,
        //         'message' => 'ID is required for updates'
        //     ];
        // }

        return [
            'status' => 'success',
            'message' => 'info successfully',
            'data' => $data
        ];
    }
    public function deleted(array $params = []): array
    {
        // Validate ID
        // if (empty($params['id'])) {
        //     return [
        //         'status' => 'error',
        //         'code' => 400,
        //         'message' => 'ID is required for deletion'
        //     ];
        // }

        return [
            'status' => 'success',
            'message' => 'Info deleted successfully',
            'data' =>$params
        ];
    }
    
    /**
     * GET /api/info/health
     * Health check endpoint
     */
    public function created($data = [],$required = []): array
    {
        // Validate required fields


        // Process the data
        return [
            'status' => 'success',
            'code' => 201,
            'message' => 'Info created successfully',
            'data' => $data,
            'required' => $required
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
                'timezone' => date_default_timezone_get()
            ],
            'application' => [
                'environment' => getenv('APP_ENV') ?: 'production',
                'debug_mode' => (bool)getenv('APP_DEBUG'),
                'version' => '1.0.0'
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
     * Get server uptime in seconds
     */
    private function getServerUptime(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 0; // Windows doesn't have a reliable way to get uptime through PHP
        }
        
        $uptime = shell_exec('cat /proc/uptime');
        if ($uptime) {
            return (int)explode(' ', $uptime)[0];
        }
        return 0;
    }
    
    /**
     * Get memory usage statistics
     */
    private function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];
    }
} 