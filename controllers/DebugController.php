<?php
declare(strict_types=1);
namespace App\Controllers;
use App\System\NexaController;

/**
 * DebugController - Debug tools dengan output yang rapi
 */
class DebugController extends NexaController
{
    public function index($params = []): void
    {
        $ordersID = $this->Storage('user')
            ->select(['id','package'])
            ->get();
        $this->dump($ordersID);
    }
}