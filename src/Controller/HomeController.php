<?php
declare(strict_types=1);

namespace Erpia2\Controller;

use Erpia2\Core\View;

final class HomeController
{
    public function index(): void
    {
        View::render('home/index', [
            'title' => 'ERPia2 - Base',
        ]);
    }
}