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
        // Commented out visitor analytics for now
        // $Agen = $this->useModels('Role/Visitor', 'analytic', [
        //     $this->session->getVisitorId(),
        //     $this->getBrowserInfo(),
        //     $this->getCurrentUrl()
        // ]); 
        //$Report=$this->refParams('Planning\\History');  
        //return $Report->Tasks(56,'2025-07-12');

     // $tasks = $this->refParams('Planning\Tasks'); 
     // $data=$tasks->teamTasks()['data'];
   
     
        $this->json([
            'error' => 'Controller not found',
            'requested_page' => $method ?? $page,
            'timestamp' => time()
        ], 404);





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