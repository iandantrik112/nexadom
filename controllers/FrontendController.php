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

    /** Satu kali per request — hindari query Storage('user') berulang */
    private ?array $dataGlobalCache = null;

    /** Satu kali per request — UA untuk variabel template {device} */
    private ?string $templateDeviceTypeCache = null;
    
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
    /**
     * Kunci yang di-spread ke template — selalu ada agar {avatar} dll. tidak tertinggal mentah.
     */
    private function dataGlobalKeys(): array
    {
        return ['id','role', 'email', 'avatar', 'nama', 'package'];
    }

    /**
     * Data user untuk assignVars / spread ke template.
     * Nilai null / false / kosong dijadikan string '' (bukan placeholder tak terisi).
     */
    private function dataGlobal(array $params = []): array
    {
        if ($this->dataGlobalCache !== null) {
            return $this->dataGlobalCache;
        }

        $empty = array_fill_keys($this->dataGlobalKeys(), '');
        try {
            $userId = $this->session?->getUserId();
            if ($userId === null || $userId === 0) {
                $this->dataGlobalCache = $empty;
                return $this->dataGlobalCache;
            }
            $row = $this->Storage('user')
                ->select($this->dataGlobalKeys())
                ->where('id', $userId)
                ->first();
            if ($row === null) {
                $this->dataGlobalCache = $empty;
                return $this->dataGlobalCache;
            }
            $data = is_array($row) ? $row : (array) $row;
            $out = $empty;
            foreach ($this->dataGlobalKeys() as $key) {
                if (!array_key_exists($key, $data)) {
                    continue;
                }
                $v = $data[$key];
                if ($v === null || $v === false) {
                    $out[$key] = '';
                } elseif (is_scalar($v)) {
                    $out[$key] = (string) $v;
                } else {
                    $out[$key] = '';
                }
            }
            $this->dataGlobalCache = $out;
            return $this->dataGlobalCache;
        } catch (\Throwable $e) {
            error_log('FrontendController::dataGlobal: ' . $e->getMessage());
            $this->dataGlobalCache = $empty;
            return $this->dataGlobalCache;
        }
    }

    /**
     * Tipe perangkat untuk template — tanpa NexaAgent::analyze() (sangat berat per halaman).
     */
    private function templateDeviceType(): string
    {
        if ($this->templateDeviceTypeCache !== null) {
            return $this->templateDeviceTypeCache;
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($ua !== '' && preg_match('/tablet|ipad|playbook|silk|kindle/i', $ua)) {
            $this->templateDeviceTypeCache = 'tablet';
        } elseif ($ua !== '' && preg_match('/Mobile|Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
            $this->templateDeviceTypeCache = 'mobile';
        } else {
            $this->templateDeviceTypeCache = 'desktop';
        }
        return $this->templateDeviceTypeCache;
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
                $this->assignVars($this->dataGlobal($params));
                $this->divert();// Frontend uses 'theme' device type
                $this->render($templatePath);
                return;
                
            } else {
                // Controller not found - for index route, render default home
                if ($page !== 'index') {
                    error_log("Index route - Controller not found: {$page}");
                }
            }
            
        } catch (\Throwable $e) {
            error_log("Home index error: " . $e->getMessage());
        }

        // Fallback: render default home index
        $this->assignVars($this->dataGlobal($params));
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
  
      
        //$this->dump($this->getRequestAnalytics()); 
        // Parse URL segments for method detection
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
        $this->assignVars(array_merge([
            'device' => $this->templateDeviceType(),
            'home' => $this->url('/home'),
            'url' => $this->url(),
            'link' => $this->url(),
            'current_page' => $page,
            'signup' => $this->url('/signup'),
            'signin' => $this->url('/signin'),
        ], $this->dataGlobal($params)));
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
                  // if ($page === 'seller' && !empty($params['toko_username'])) {
                  //     $templatePath = 'seller/toko';
                  // } else {
                      $templatePath = $this->isFile($page,$thirdSegment,$finalMethod);
                  // }
                  $this->divert();
                  $this->render($templatePath);
                return;
            } else {
                // Controller not found - redirect to home
                $this->redirect($this->url('/home'));
                return;
            }
        } catch (\Throwable $e) {
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