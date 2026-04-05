<?php
declare(strict_types=1);
namespace App\Controllers\Frontend;
use App\System\NexaController;

/**
 * ProfilController - Frontend Controller
 * URL: /profil
 * Template: templates/theme/profil/index.html
 */
class ProfilController extends NexaController
{
    public function index(array $params = []): void
    {
        $this->assignVars(['page_title' => 'Profil', 'base_url' => $this->getBaseUrl()]);
    }
}
