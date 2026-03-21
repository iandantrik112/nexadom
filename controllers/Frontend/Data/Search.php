<?php
declare(strict_types=1);
namespace App\Controllers\Frontend\Data;

use App\System\NexaController;

/**
 * MakanMinum Data Controller
 * Handle data processing for Makan Minum products
 */
class search extends NexaController
{
    /**
     * Get product list data with pagination
     * 
     * @param array $params Parameters from route
     * @return array Product data with pagination
     */
    public function list(array $params = []): array
    {
        $page = $this->pagesIntRequest();
        $requestParams = $this->paramsKeys();
        
        // Query data dari model
        $searchResults = $this->useModels('Product', 'search', [
            $requestParams['i'],
            $requestParams['sort'],
            $page,
            ''
        ]);
        
        // Generate base URL untuk pagination
        // Check if user is logged in (dashboard context)
        if ($this->isLoggedIn()) {
            // Dashboard: /username/product/makan_minum/pages/
            $username = $this->getSession()->getUserSlug();
            $baseUrl = $this->url('/' . $username . '/search?i='.$requestParams['i'].'&pages/');
        } else {

            // Frontend: /product/makan_minum/pages/
            $baseUrl = $this->url('/search?i='.$requestParams['i'].'&pages/');
        }
        
        // Generate pagination HTML
        $paginationHTML = $this->NexaPagination()->render(
            $searchResults['current_page'],
            $searchResults['last_page'], 
            $baseUrl
        );
        
        // Get device info
        $analysis = $this->getBrowserInfo();
        
        return [
            'products' => $searchResults['data'],
            'pagination' => $paginationHTML,
            'total_records' => $searchResults['total'],
            'current_records' => count($searchResults['data']),
            'device_type' => $analysis['device_type'],
            'search_query' => $requestParams['i'],
            'current_page' => $searchResults['current_page'],
            'last_page' => $searchResults['last_page'],
        ];
    }
    
    /**
     * Get product detail by ID
     * 
     * @param int|string $productId Product ID
     * @return array Product detail data
     */
    public function detail($productId): array
    {
        // Validate product ID
        if (empty($productId) || !is_numeric($productId)) {
            return [];
        }
        
        $data = $this->Storage('products')
            ->select(['*'])
            ->where('id', (int)$productId)
            ->first();
        
        if (empty($data)) {
            return [];
        }
        
        return $data;
    }
    
    /**
     * Parse product slug to get ID and name
     * Example: "nasi-goreng-123" → ['id' => 123, 'name' => 'nasi-goreng']
     * 
     * @param string $slug Product slug
     * @return array ['id' => int|null, 'name' => string, 'display_name' => string]
     */
    public function parseSlug(string $slug): array
    {
        $slugParts = explode('-', $slug);
        $productId = null;
        $nameSlug = $slug;
        
        // Check if last part is numeric (ID)
        if (count($slugParts) > 1 && is_numeric(end($slugParts))) {
            $productId = array_pop($slugParts);
            $nameSlug = implode('-', $slugParts);
        }
        
        return [
            'id' => $productId,
            'name' => $nameSlug,
            'display_name' => ucwords(str_replace('-', ' ', $nameSlug))
        ];
    }
}
