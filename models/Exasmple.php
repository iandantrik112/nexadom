<?php
declare(strict_types=1);

namespace App\Models;

use App\System\NexaModel;

/**
 * User Model untuk useModels() example
 */
class News extends NexaModel
{
    protected $table = 'news';
   
    
    private $controller = null;
    
    public function setController($controller): self {
        $this->controller = $controller;
        return $this;
    }

 
   
    
    public function data(string $search): ?array {
        return [
            'id' => 1,
            'name' => 'John Doe',
            'email' => $search,
            'table' => $this->table
        ];
    }
} 