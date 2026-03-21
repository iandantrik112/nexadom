<?php
declare(strict_types=1);
namespace App\Controllers;
use App\System\NexaController;


/**
 * HomeController - Enhanced with Integrated NexaNode for Frontend
 * Now uses Frontend namespace for public-facing pages
 */
class FrontendController extends NexaController
{
    private string $nodeNamespace = 'App\\Controllers\\Frontend\\';
    
    /**
     * Constructor - No longer needs NexaNode instantiation
     * 
     * @param object $view View handler
     * @param array $deviceLayouts Device layout configuration
     */
    public function __construct($view, array $deviceLayouts = [])
    {
        parent::__construct($view, $deviceLayouts);
        
        // ========================================================================
        // SETUP FRONTEND NAMESPACE untuk nodeController()
        // ========================================================================
        $this->setControllerNamespace($this->nodeNamespace);
        
    }
     private function dataGlobal(array $params = []): array{
        // return $this->useData('General', 'data', ['all']);
    }

    
    /**
     * Home index page - /
     * Enhanced with integrated NexaNode functionality for Frontend
     */
    public function index(array $params = []): void
    {
        // Redirect to dashboard if user is logged in
        // if ($this->isLoggedIn()) {
        //     $username = $this->getSession()->getUserSlug();
        //     $this->redirect($this->url('/' . $username));
        //     return;
        // }

        // $this->assignVars($this->dataGlobal($params));
        $page = $params['page'] ?? 'index';
        
        try {
            // Check if controller exists and execute it
            if ($this->controllerExists($page)) {
                // Execute controller - methods are now void and use assignVars()
                $this->nodeController($page, $params);
                
                // Controller has already set template variables via assignVars()
                // Render page-specific template
                if ($page === 'index') {
                    $templatePath = 'index';
                } else {
                    $templatePath = strtolower($page) . '/index';
                }
                $this->divert();// Frontend uses 'theme' device type
                $this->render($templatePath);
                return;
                
            } else {
                // Controller not found - for index route, render default home
                if ($page !== 'index') {
                    error_log("Index route - Controller not found: {$page}");
                }
            }
            
        } catch (Exception $e) {
            error_log("Home index error: " . $e->getMessage());
        }

        // Fallback: render default home index
        $this->divert();
        $this->render('index');
    }
    
    /**
     * Frontend page routing - /{page}/{method?}
     * Supports dynamic method routing for public pages
     */
    public function page(array $params = []): void
    {
        $page = $params['page'] ?? '';
        
        // ========================================================================
        // SPECIAL ROUTING: Detect ORD-* pattern - REQUIRES LOGIN
        // ========================================================================
        if (!empty($page) && preg_match('/^ORD-/i', $page)) {
            // ORD-* pattern detected - redirect to dashboard if logged in, signin if not
            if ($this->isLoggedIn()) {
                $username = $this->getSession()->getUserSlug();
                // Redirect to dashboard with order code
                $this->redirect($this->url('/' . $username . '/' . $page));
            } else {
                // Not logged in - redirect to signin
                $this->redirect($this->url('/home'));
            }
            return;
        }
        
        // ========================================================================
        // SPECIAL ROUTING: seller/{username_toko} pattern
        // ========================================================================
        if ($page === 'seller') {
            $secondSegment = $this->getSlug(1);
            if (!empty($secondSegment)) {
                // Check if username_toko exists
                $tokoData = $this->Storage('toko')
                    ->select(['id', 'userid', 'nama_toko', 'username_toko', 'logo_toko', 'rating', 'chat'])
                    ->where('username_toko', $secondSegment)
                    ->first();
                
                if (!empty($tokoData['id'])) {
                    // Valid toko found - handle in SellerController
                    $params['toko_data'] = $tokoData;
                    $params['toko_username'] = $secondSegment;
                } else {
                    // Toko not found, redirect to home
                    $this->redirect($this->url('/home'));
                    return;
                }
            }
        }
        
        // Redirect to dashboard if user is logged in
        if ($this->isLoggedIn()) {
            $username = $this->getSession()->getUserSlug();
            
            // Preserve the current page path in dashboard
            if (!empty($page)) {
                $this->redirect($this->url('/' . $username . '/' . $page));
            } else {
                $this->redirect($this->url('/' . $username));
            }
            return;
        }
      
        //$this->dump($this->getRequestAnalytics()); 
        // Parse URL segments for method detection
        $pathSegments = $this->getRelativePathSegments();
        $requestedMethod = 'index'; // default fallback
        $thirdSegment =$this->getSlug(1);
        if (!empty($thirdSegment)) {
            $requestedMethod = $thirdSegment;
        }
        // Method validation & fallback system
        $targetClass = $this->nodeNamespace . ucfirst($page) . 'Controller';
        $finalMethod = $requestedMethod;
        $usedFallback = false;
        if (class_exists($targetClass)) {
            // Check if requested method exists, fallback to index if not
            if (!method_exists($targetClass, $requestedMethod)) {
                $finalMethod = 'index';
                $usedFallback = true;
            }
        }
        $menuData = null;
        // if ($Navigasi && isset($Navigasi->data_value)) {
        //     $menuData = $Navigasi->data_value;
        // }
        $analysis = $this->getBrowserInfo();
        $this->assignVars([
            'device' => $analysis['device_type'],
            'home' => $this->url('/home'),
            'url' => $this->url(),
            'link' => $this->url(),
            'product' => $this->url('/product'),
            'search' => $this->url('/search'),
            'page_title' => ucfirst($page),
            'current_page' => $page,

            'signup' => $this->url('/signup'),
            'signin' => $this->url('/signin'),
            // ...$this->dataGlobal($params)
        ]);
        try {
            // FIXED: Pass Frontend namespace to controllerExists()
            if ($this->controllerExists($page, $this->nodeNamespace)) {
                $this->callController($page)
                     ->method($finalMethod)
                     ->withParams($params)
                     ->param('method', $finalMethod)
                     ->param('requested_method', $requestedMethod)
                     ->param('used_fallback', $usedFallback)
                     ->param('frontend_context', false)
                     ->execute();

                  // Special template routing for seller/toko
                  if ($page === 'seller' && !empty($params['toko_username'])) {
                      $templatePath = 'seller/toko';
                  } else {
                      $templatePath = $this->isFile($page,$thirdSegment,$finalMethod);
                  }
                  $this->divert();
                  $this->render($templatePath);
                return;
            } else {
                // Controller not found - redirect to home
                $this->redirect($this->url('/home'));
                return;
            }
        } catch (Exception $e) {
            // Error occurred - redirect to home
            error_log("Frontend page error: " . $e->getMessage() . " - Redirecting to /home");
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->redirect($this->url('/home'));
            return;
        }
    }
    
    /**
     * Get navigation data from database
     * 
     * @return object|null Navigation data
     */
  

}