<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $jsonPath = database_path('cleaned_company_data_urls.json');
        
        if (!file_exists($jsonPath)) {
            throw new \Exception("File JSON tidak ditemukan di {$jsonPath}");
        }

        $json = file_get_contents($jsonPath);
        $companies = json_decode($json, true);

        if (!is_array($companies)) {
            throw new \Exception("JSON tidak valid atau format tidak sesuai (harus berupa array).");
        }

        foreach ($companies as $data) {
            Company::create([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? 'Unknown',
                'industry' => $data['industry'] ?? 'Unknown',
                'visi' => $data['visi'] ?? null,
                'misi' => $data['misi'] ?? null,
                'company_featured_employees_collection' => json_encode(
                    array_map(fn($item) => $item['url'], $data['company_featured_employees_collection'] ?? [])
                ),
                'company_similar_collection' => json_encode(
                    array_map(fn($item) => $item['url'], $data['company_similar_collection'] ?? [])
                ),
            ]);
        }
    }
}
