<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\View;

final class HomeController
{
    public function index(): void
    {
        View::render('home/index', [
            'title' => 'ERP2 - Base',
        ]);
    }
}