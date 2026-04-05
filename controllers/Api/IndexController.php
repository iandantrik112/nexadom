<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\System\NexaController;

/**
 * IndexController - API Controller for handling project and task-related endpoints
 * 
 * This controller provides endpoints for:
 * - Health check and index data
 * - User tasks management
 * - Task logs and validation
 * - Project information retrieval
 */
class IndexController extends NexaController
{
    /**
     * Health check endpoint and main index data retrieval
     * 
     * @return array Returns merged index data for project ID 56
     */
    public function index(): array
    {
        return [
            'status' => 'success',
            'message' => 'Hello from API!',
            'timestamp' => time(),
            'greeting' => 'Hello World!'
        ];



     //    return $data;
        //return $this->indexData(56,54,22);
    }
    /**
     * Get comprehensive project data including tasks and activities
     * 
     * @param int $id_project Project ID
     * @return array|null Project data matrix or null if project not found
     */
    public function page($prams)
    {

           $this->json([
            'error' => 'Controller not found',
    
            'timestamp' => time()
        ], 404);
    }
} 