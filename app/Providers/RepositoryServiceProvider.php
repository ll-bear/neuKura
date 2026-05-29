<?php

namespace App\Providers;

use App\Repositories\BookmarkRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\Interfaces\BookmarkRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(BookmarkRepositoryInterface::class, BookmarkRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
