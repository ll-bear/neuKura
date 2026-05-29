<?php

namespace App\Repositories\Interfaces;

interface CategoryRepositoryInterface
{
    public function getCategories(int $userId);
    public function store(array $data);
    public function update(int $id, array $data);
    public function destroy(int $id);
    public function sort(array $data);
    public function getByUser(int $userId);
}