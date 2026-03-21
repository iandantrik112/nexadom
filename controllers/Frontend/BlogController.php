<?php
declare(strict_types=1);
namespace App\Controllers\Frontend;

use App\System\NexaController;

/**
 * BlogController - Handle blog routes with year and params
 * 
 * Route: $router->get('{Y}/{params}', 'BlogController@detail');
 * 
 * Examples:
 * - /2024/article-title → Y=2024, params=article-title
 * - /2023/category/tech → Y=2023, params=category/tech
 * - /2022/author/john/post/123 → Y=2022, params=author/john/post/123
 */
class BlogController extends NexaController
{
    

    public function index(array $params = []): void
    {
       
        $this->assignVars([
            'page_title' => 'About Us - Our Company',
            'page_description' => 'Learn more about our company and mission',
            'current_page' => 'about',
            'is_public_page' => true,
        ]);
    }



    
    /**
     * Handle blog detail route - {Y}/{params}
     * 
     * @param string $Y The year parameter from URL
     * @param string $params The remaining URL parameters
     * @return void
     */
    public function detail(string $Y = '', string $params = ''): void
    {
        // Use current year if no year provided
         $this->render('about/datac');
        $year = !empty($Y) ? $Y : date('Y');
   
        // Convert URL parameters to array if needed
        $paramArray = !empty($params) ? explode('/', $params) : [];
        $this->dump($this->getRequestAnalytics());
        
        // Now you can work with $year and $paramArray
        // Example: $year = "2024", $paramArray = ["article-title"]
        // or $year = "2023", $paramArray = ["category", "tech"]
        
        echo "Year: " . $year . "\n";
        echo "Parameters: " . implode(', ', $paramArray);
    }



    
}
