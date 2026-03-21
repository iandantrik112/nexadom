<?php
declare(strict_types=1);
namespace App\Controllers\Frontend;
use App\System\NexaController;

/**
 * ExampleController - Demonstrates NexaJs usage
 * Shows how to send data from PHP to JavaScript
 */
class IndexController extends NexaController
{
    /**
     * Example page with dynamic data sent to JavaScript
     */
    public function index(array $params = []): void
    {
        $analysis = $this->getBrowserInfo();
        // $baru=  $this->useModels('Product', 'Baru', [6]);
        // $this->nexaBlock('row', $baru);

        // Default SEO image
        $defaultOgImage = $this->url('images/sibeto.png');
        
        // Setup basic page variables with SEO optimization
        $this->assignVars([
            // Basic Meta
            'page_title' => 'Program Beasiswa SIAP KULIAH - Disparpora Pohuwato',
            'page_description' => 'Program Bantuan Beasiswa SIAP KULIAH dan SIAP HAFALAN QURAN untuk mahasiswa S1, S2, dan penghafal Al-Quran dari Kabupaten Pohuwato. Daftar sekarang secara online!',
            'page_keywords' => 'beasiswa pohuwato, siap kuliah, beasiswa s1, beasiswa s2, beasiswa hafalan quran, disparpora pohuwato, beasiswa mahasiswa, bantuan pendidikan, beasiswa gorontalo',
            
            // Open Graph
            'og_type' => 'website',
            'og_url' => $this->url('/home'),
            'og_title' => 'Program Beasiswa SIAP KULIAH - Disparpora Pohuwato',
            'og_description' => 'Program Bantuan Beasiswa untuk mahasiswa S1, S2, dan penghafal Al-Quran. Daftar online, proses cepat, dan transparan.',
            'og_image' => $defaultOgImage,
            
            // Twitter Card
            'twitter_url' => $this->url('/home'),
            'twitter_title' => 'Program Beasiswa SIAP KULIAH - Disparpora Pohuwato',
            'twitter_description' => 'Beasiswa untuk mahasiswa S1, S2, dan penghafal Al-Quran dari Kabupaten Pohuwato. Pendaftaran online tersedia!',
            'twitter_image' => $defaultOgImage,
            
            // Schema & Canonical
            'canonical_url' => $this->url('/home'),
            'schema_image' => $defaultOgImage,
            
            // Navigation
            'device' => $analysis['device_type'],
            'search_query' => '',
            'home' => $this->url('/home'),
            // 'search' => $this->url('/search'),
            // 'product' => $this->url('/product'),
            'signup' => $this->url('/signup'),
            'signin' => $this->url('/signin')
        ]);   
    }


   
     public function FetchEvents(array $params = []){
           $this->eventsAccess($params);
     }

     public function FetchControllers(){
        return $this->eventsControllers();
     }


    public function FetchModels(){
         $this->eventsModel();
    }
    
} 