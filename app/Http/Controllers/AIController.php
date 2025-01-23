<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Orhanerday\OpenAi\OpenAi;

class AIController extends Controller
{
    public function profileCompany(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'company_name' => 'required|string',
        ]);

        $companyName = $request->company_name;

        // 2. Cari perusahaan di database
        $company = DB::table('companies')
            ->where('name', 'LIKE', '%' . $companyName . '%')
            ->first();

        if (!$company) {
            return response()->json(['error' => 'Perusahaan tidak ditemukan.'], 404);
        }

        $featuredEmployees = collect(json_decode($company->company_featured_employees_collection, true))
            ->map(fn($comp) => ['url' => $comp])
            ->values();

        $similarCompanies = collect(json_decode($company->company_similar_collection, true))
            ->map(fn($comp) => ['url' => $comp])
            ->values();

        // 3. Pencarian data tambahan menggunakan Google Search API
        $additionalDetails = [
            'address' => $this->searchCompanyDetails($companyName, 'address'),
            'phone' => $this->searchCompanyDetails($companyName, 'phone'),
            'email' => $this->searchCompanyDetails($companyName, 'email'),
            'website' => $this->searchCompanyDetails($companyName, 'website'),
            'founded' => $this->searchCompanyDetails($companyName, 'founded'),
            'business_form' => $this->searchCompanyDetails($companyName, 'business_form'),
            'employees' => $this->searchCompanyDetails($companyName, 'employees'),
            'management' => $this->searchCompanyDetails($companyName, 'management'),
            'products' => $this->searchCompanyDetails($companyName, 'products'),
            'financial' => $this->searchCompanyDetails($companyName, 'financial'),
            'subsidiaries' => $this->searchCompanyDetails($companyName, 'subsidiaries'),
            'relations' => $this->searchCompanyDetails($companyName, 'relations'),
        ];

        // Format data tambahan untuk prompt
        $additionalInfo = "";
        foreach ($additionalDetails as $key => $detail) {
            $info = $detail ? implode("\n", array_map(fn($item) => $item['snippet'], $detail)) : "Tidak ditemukan.";
            $additionalInfo .= ucfirst($key) . ": " . $info . "\n";
        }

        $rekomendasiProduk = DB::table('corporate_products')
        ->select('nama_produk', 'deskripsi', 'plafon', 'interest_rate', 'benefits', 'requirements', 'terms_and_conditions')
        ->get()
        ->toArray();


        $produkList = collect($rekomendasiProduk)->map(function ($product) {
            $benefits = implode("\n    - ", json_decode($product->benefits, true));
            $requirements = implode("\n    - ", json_decode($product->requirements, true));
            $terms = implode("\n    - ", json_decode($product->terms_and_conditions, true));

            return "- Nama Produk: {$product->nama_produk}
            Deskripsi: {$product->deskripsi}
            Plafon: {$product->plafon}
            Interest Rate: {$product->interest_rate}
            Benefits:
            - {$benefits}
            Requirements:
            - {$requirements}
            Terms & Conditions:
            - {$terms}";
        })->implode("\n\n");


        // 4. Siapkan prompt untuk OpenAI
        $prompt = "
        Mohon buat profil perusahaan yang lengkap untuk: {$company->name}
        berdasarkan struktur berikut:

        **Profil Perusahaan**

        **Informasi Dasar Perusahaan**
        Nama Perusahaan: {$company->name} [Tambahkan dari pencarian]
        Deskripsi Perusahaan: {$company->description}
        Jenis Industri: {$company->industry}

        **Informasi Tambahan**
        Alamat Kantor Pusat: [Lengkapi dari pencarian]
        Nomor Telepon: [Lengkapi dari pencarian]
        Alamat Email: [Lengkapi dari pencarian]
        Website Resmi: [Lengkapi dari pencarian]
        Tahun Berdiri: [Lengkapi dari pencarian]
        Bentuk Badan Usaha: [Lengkapi dari pencarian]
        Jumlah Karyawan: [Lengkapi dari pencarian]
        Struktur Manajemen: [Lengkapi dari pencarian]

        **Produk dan Layanan**
        [Lengkapi dari pencarian]

        **Anak Perusahaan**
        [Lengkapi dari pencarian]

        **Relasi Perusahaan**
        [Lengkapi dari pencarian]

        Informasi tambahan dari pencarian web:
        {$additionalInfo}

        **Rekomendasi Produk**
        Dari data berikut, pilih produk yang sesuai dengan perusahaan:
        {$produkList}
        ";

        // 5. Kirim permintaan ke OpenAI
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful business profiler. Provide detailed company profiles based on the given data in Indonesian.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];

        try {
            $open_ai = new OpenAi(env('OPENAI_API_KEY'));
            $response = $open_ai->chat([
                'model' => 'gpt-4o',
                'messages' => $messages,
            ]);

            $responseData = json_decode($response, true);

            if (!isset($responseData['choices'][0]['message']['content'])) {
                return response()->json(['error' => 'Gagal mendapatkan respons dari OpenAI.'], 500);
            }

            // 6. Kembalikan respons ke pengguna
            return response()->json([
                'company_name'       => $company->name,
                'profile'            => $responseData['choices'][0]['message']['content'],
                'featured_employees' => $featuredEmployees,
                'similar_companies'  => $similarCompanies,
                'additional_details' => $additionalDetails,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error with OpenAI: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memproses data dengan OpenAI: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function searchCompanyDetails($query, $type)
    {
        $apiKey = env('GOOGLE_SEARCH_API_KEY');
        $searchEngineId = env('GOOGLE_SEARCH_ENGINE_ID');
        $url = "https://www.googleapis.com/customsearch/v1";

        // Sesuaikan query berdasarkan jenis informasi
        $queries = [
            'address' => $query . ' address headquarters',
            'phone' => $query . ' contact number',
            'email' => $query . ' official email',
            'website' => $query . ' official website',
            'founded' => $query . ' founded year',
            'business_form' => $query . ' legal entity',
            'employees' => $query . ' number of employees',
            'management' => $query . ' management structure',
            'products' => $query . ' products and services',
            'financial' => $query . ' financial performance',
            'subsidiaries' => $query . ' subsidiaries',
            'relations' => $query . ' company relations',
        ];

        $response = Http::get($url, [
            'q' => $queries[$type],
            'key' => $apiKey,
            'cx' => $searchEngineId,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['items'] ?? [];
        }

        return null;
    }
}
