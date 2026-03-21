<?php
declare(strict_types=1);
namespace App\Controllers\Admin\Example;
use App\System\NexaController;

/**
 * TextController - Admin Controller
 * URL: /{username}/text
 * Template: templates/dashboard/text/index.html
 */
class TextController extends NexaController
{
    public function index(array $params = []): void
    {
        $username = $params['username'] ?? $this->getSession()->getUserSlug();

        $this->assignVars([
            'page_title' => 'Text',
            'username' => $username,
        ]);
    }
}
