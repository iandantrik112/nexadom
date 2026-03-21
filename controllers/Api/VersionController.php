<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * Test Controller untuk API endpoints
 */
class VersionController extends NexaController
{
    /**
     * Test endpoint
     */
    public function index(): array
    {
        return [
            'status' => 'success',
            'message' => 'Test API endpoint working!',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s'),
            'data' => [
                'test' => true,
                'version' => '1.0.0'
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
     * NexaUI Version Check endpoint (PUT request)
     * Returns update information for NexaUI framework
     * URL: PUT /api/version
     */
    public function red($data = [], $params = []): array
    {
        // Current and latest version
        $currentVersion = '1.0.22';
        $latestVersion = '1.0.23';
        
        // Check if update is available
        $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
        
        if (!$updateAvailable) {
            return [
                'version' => $currentVersion,
                'available' => false,
                'message' => 'You are using the latest version',
                'timestamp' => time(),
                'data' => $data,
                'params' => array_merge([
                    'endpoints' => '/api/version',
                    'status' => true,
                    'authorization' => 'NX_XXXXXXXXXXXXXXXXX',
                    'expired' => false,
                    'allowed_methods' => []
                ], $params)
            ];
        }
        
        // Update available - return update information
        return [
            'version' => $latestVersion,
            'downloadUrl' => $this->url('/api/version/download/' . $latestVersion),
            'changelog' => 'Fixed bugs, improved performance, added new features',
            'currentVersion' => $currentVersion,
            'available' => true,
            'timestamp' => time(),
            'data' => $data,
            'params' => array_merge([
                'endpoints' => '/api/version',
                'status' => true,
                'authorization' => 'NX_XXXXXXXXXXXXXXXXX',
                'expired' => false,
                'allowed_methods' => []
            ], $params)
        ];
    }
    
    /**
     * Download NexaUI update file
     * URL: GET /api/version/download/1.0.14
     */
    public function download($data = [], $params = []): void
    {
        // Check if request method is GET
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($requestMethod !== 'GET') {
            http_response_code(405);
            header('Content-Type: application/json');
            header('Allow: GET');
            echo json_encode([
                'status' => 'error',
                'message' => 'Method Not Allowed',
                'error' => 'The requested method ' . $requestMethod . ' is not allowed for this URL. Use GET method instead.',
                'allowed_methods' => ['GET'],
                'timestamp' => time()
            ]);
            exit;
        }
        
        // Get version from multiple sources (path parameter, query string, or params)
        $version = null;
        
        // Try to get from path parameter first (priority for /api/version/download/1.0.14)
        // Routing might pass version as part of method or in params
        if (method_exists($this, 'getSlug')) {
            // Try slug3 first (for /api/version/download/1.0.14)
            $slug3 = $this->getSlug(3);
            if ($slug3 && preg_match('/^\d+\.\d+\.\d+/', trim($slug3))) {
                $version = trim($slug3);
            }
            // Try slug4 (for /dev/api/version/download/1.0.14)
            if (!$version) {
                $slug4 = $this->getSlug(4);
                if ($slug4 && preg_match('/^\d+\.\d+\.\d+/', trim($slug4))) {
                    $version = trim($slug4);
                }
            }
        }
        
        // Try from params array (routing might pass it here)
        if (!$version) {
            $version = $params['version'] ?? null;
            // Also check if version is in other params keys
            foreach ($params as $key => $value) {
                if (is_string($value) && preg_match('/^\d+\.\d+\.\d+/', trim($value))) {
                    $version = trim($value);
                    break;
                }
            }
        }
        
        // Try from query parameter
        if (!$version) {
            $version = $_GET['version'] ?? null;
        }
        
        // Try from page params (if version is in URL path)
        if (!$version && isset($params['page'])) {
            $pageParts = explode('/', $params['page']);
            // Look for version pattern (x.y.z) in page parts
            foreach ($pageParts as $part) {
                if (preg_match('/^\d+\.\d+\.\d+/', trim($part))) {
                    $version = trim($part);
                    break;
                }
            }
        }
        
        // Last resort: check if method was called with version in method name pattern
        // For URL like /api/version/download/1.0.14, routing might pass 'download/1.0.14' as method
        if (!$version && isset($params['method'])) {
            $methodParts = explode('/', $params['method']);
            foreach ($methodParts as $part) {
                if (preg_match('/^\d+\.\d+\.\d+/', trim($part))) {
                    $version = trim($part);
                    break;
                }
            }
        }
        
        // Trim whitespace and validate
        if ($version) {
            $version = trim($version);
        }
        
        if (!$version || empty($version)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Version parameter is required',
                'hint' => 'Use: /api/version/download/1.0.14 or ?version=1.0.14',
                'timestamp' => time()
            ]);
            exit;
        }
        
        // Path to ZIP file (relative to controller location)
        // From controllers/Api/ to version/ folder
        $zipPath = __DIR__ . '/../../version/' . $version . '.zip';
        
        // Check if file exists
        if (!file_exists($zipPath)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Update file not found for version: ' . $version,
                'version' => $version,
                'path' => $zipPath,
                'timestamp' => time()
            ]);
            exit;
        }
        
        // Set headers for file download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="nexaui-' . $version . '.zip"');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Pragma: no-cache');
        
        // Output file
        readfile($zipPath);
        exit;
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