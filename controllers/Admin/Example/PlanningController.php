<?php
namespace App\Controllers\Admin\Example;
use App\System\NexaController;

/**
 * PlanningController - Simplified dengan 2 method saja
 */
class PlanningController extends NexaController
{

    // const STATUS_PLANNING = 'planning';
    // const STATUS_ACTIVE = 'active';
    // const STATUS_ON_HOLD = 'on_hold';
    // const STATUS_COMPLETED = 'completed';
    // const STATUS_CANCELLED = 'cancelled';
    
    // /**
    //  * Valid priority values
    //  */
    // const PRIORITY_LOW = 'low';
    // const PRIORITY_MEDIUM = 'medium';
    // const PRIORITY_HIGH = 'high';
    // const PRIORITY_URGENT = 'urgent';


    /**
     * Default method - Show example index page with form
     */
  public function index()
    {
        // 1. Assign global variables
        $this->assignVars([
            'site_name' => 'My Amazing Blog',
            'page_title' => 'Blog Dashboard',
            'current_year' => date('Y')
        ]);

        // 2.assignNestedData
        $this->sectionBlog([
            'menu' => [
                ['name' => 'Edit', 'icon' => 'fas fa-edit', 'action' => 'edit'],
                ['name' => 'Delete', 'icon' => 'fas fa-trash', 'action' => 'delete'],
                ['name' => 'Share', 'icon' => 'fas fa-share', 'action' => 'share']
            ],
            'social_links' => [
                ['name' => 'Facebook', 'url' => 'https://facebook.com', 'icon' => 'fab fa-facebook'],
                ['name' => 'Twitter', 'url' => 'https://twitter.com', 'icon' => 'fab fa-twitter'],
                ['name' => 'Instagram', 'url' => 'https://instagram.com', 'icon' => 'fab fa-instagram']
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Blog', 'url' => '/blog'],
                ['label' => 'Dashboard', 'url' => '/blog/dashboard']
            ]
        ]);

        // Debug: Assign some test variables to see if they work
        $this->assignVars([
            'test_variable' => 'This is a test variable',
            'debug_info' => 'sectionBlog has been called'
        ]);

        // 3. Assign regular block data untuk kategori
        $this->assignBlock('categories', [
            ['name' => 'Technology', 'id' => 1, 'post_count' => 15],
            ['name' => 'Lifestyle', 'id' => 2, 'post_count' => 8],
            ['name' => 'Travel', 'id' => 3, 'post_count' => 12]
        ]);

        // 4. Assign nested data untuk posts dengan comments
        $this->assignNestedData('posts', [
            [
                'title' => 'Getting Started with PHP',
                'slug' => 'getting-started-php',
                'author' => 'John Doe',
                'date' => '2024-01-15',
                'category_id' => 1,
                'comments' => [
                    ['author' => 'Jane Smith', 'comment' => 'Great tutorial!'],
                    ['author' => 'Bob Wilson', 'comment' => 'Very helpful, thanks!']
                ]
            ],
            [
                'title' => 'Best Travel Destinations 2024',
                'slug' => 'best-travel-destinations-2024',
                'author' => 'Sarah Johnson',
                'date' => '2024-01-20',
                'category_id' => 3,
                'comments' => [
                    ['author' => 'Mike Brown', 'comment' => 'Amazing places!']
                ]
            ]
        ]);

      
    }








} 
