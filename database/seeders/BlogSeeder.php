<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $cats = collect(['Sağlıklı Yaşam', 'Tarifler', 'Üretici Hikâyeleri'])
            ->mapWithKeys(fn ($name, $i) => [$name => BlogCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $i, 'is_active' => true]
            )->id]);

        $posts = [
            ['Organik Beslenmeye Başlamak İçin 5 Adım', 'Sağlıklı Yaşam', 'Organik beslenmeye geçişi kolaylaştıracak pratik öneriler.'],
            ['Mevsiminde Tüketmek Neden Önemli?', 'Sağlıklı Yaşam', 'Mevsim ürünlerinin tazelik ve besin değeri avantajları.'],
            ['Zeytinyağlı Enginar Tarifi', 'Tarifler', 'Bahar sofralarının vazgeçilmezi, pratik bir tarif.'],
            ['Organik Mercimek Çorbası', 'Tarifler', 'Organik kırmızı mercimekle 20 dakikada nefis bir çorba.'],
            ['Fırında Köy Peyniri ile Sebze', 'Tarifler', 'Mevsim sebzeleri ve köy peyniriyle pratik fırın yemeği.'],
            ['Ege\'den Bir Üretici: Zeytin Hasadı', 'Üretici Hikâyeleri', 'Erken hasat zeytinyağının arkasındaki emek.'],
        ];

        foreach ($posts as $i => [$title, $catName, $excerpt]) {
            Post::firstOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'blog_category_id' => $cats[$catName],
                    'excerpt' => $excerpt,
                    'content' => '<p>' . $excerpt . '</p><p>Bu yazı örnek içeriktir; admin panelinden düzenlenebilir. Organik yaşam ve sağlıklı beslenme üzerine değerli bilgiler burada paylaşılır.</p>',
                    'is_published' => true,
                    'published_at' => now()->subDays($i * 3),
                    'sort_order' => $i,
                ]
            );
        }
    }
}
