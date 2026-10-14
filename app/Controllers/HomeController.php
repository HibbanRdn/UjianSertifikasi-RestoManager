<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\MenuItemRepository;

final class HomeController
{
    public function __construct(
        private readonly View $view,
        private readonly CategoryRepository $categories,
        private readonly MenuItemRepository $menuItems
    ) {
    }

    public function index(Request $request): void
    {
        $this->view->render('public/home', [
            'pageTitle' => 'Beranda',
            'categories' => $this->categories->activeWithVisibleMenuCount(),
            'featuredMenu' => $this->menuItems->publicCatalog(limit: 4),
        ]);
    }

    public function categories(Request $request): void
    {
        $this->view->render('public/categories', [
            'pageTitle' => 'Kategori Menu',
            'categories' => $this->categories->activeWithVisibleMenuCount(),
        ]);
    }

    public function menu(Request $request): void
    {
        $requestedCategory = $request->query('category');
        $categoryId = ctype_digit($requestedCategory) ? (int) $requestedCategory : null;
        $search = mb_substr(trim($request->query('q')), 0, 80);

        $this->view->render('public/menu', [
            'pageTitle' => 'Menu',
            'categories' => $this->categories->activeWithVisibleMenuCount(),
            'menuItems' => $this->menuItems->publicCatalog($categoryId, search: $search),
            'selectedCategory' => $categoryId,
            'searchQuery' => $search,
        ]);
    }
}
