<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'order' => 1, 'name' => 'レシピ'],
            ['id' => 2, 'order' => 2, 'name' => 'グルメ'],
            ['id' => 3, 'order' => 3, 'name' => 'お取り寄せ'],
            ['id' => 4, 'order' => 4, 'name' => 'IT'],
            ['id' => 5, 'order' => 5, 'name' => '健康・美容'],
            ['id' => 6, 'order' => 6, 'name' => '旅行'],
            ['id' => 7, 'order' => 7, 'name' => 'エンタメ'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::updateOrCreate(['id' => $category['id']], array_merge($category, ['user_id' => null]));
        }
    }
}
