<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CorporateProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $corporateProducts = json_decode(file_get_contents(database_path('seeders/bjb_corporate_products.json')), true)['produkBankBJB'];

        foreach ($corporateProducts as $product) {
            DB::table('corporate_products')->insert([
                'nama_produk' => $product['namaProduk'],
                'kategori_perusahaan' => $product['kategoriPerusahaan'],
                'list_produk' => $product['listProduk'],
                'jenis_pembiayaan' => $product['jenisPembiayaan'],
                'deskripsi' => $product['deskripsi'],
                'plafon' => $product['plafon'],
                'requirements' => json_encode($product['requirements']),
                'benefits' => json_encode($product['benefits']),
                'interest_rate' => $product['interestRate'],
                'terms_and_conditions' => json_encode($product['termsAndConditions']),
            ]);
    }
}
}
