<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;

/**
 * DocsController - API Controller
 * URL: /api/docs
 */
class DocsController extends NexaController
{

    public function index(): array
    {
        // Argumen ke useData adalah *daftar argumen* method: meta([]) = satu parameter array kosong.
        $items = $this->useData('Search', 'meta', [[]]);
        return [
            'items' => $items,
        ];
    }

}
