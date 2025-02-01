<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache; // [MODIFIED] Import Cache
use Orhanerday\OpenAi\OpenAi;
use Barryvdh\DomPDF\Facade\Pdf;

class AIController extends Controller
{
    private function searchWithGoogleMaps($companyName, $type)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $fieldMap = [
            'address' => 'formatted_address',
            'phone' => 'formatted_phone_number',
            'website' => 'website',
        ];

        try {
            // Step 1: Cari Place ID menggunakan Find Place API
            $findPlaceResponse = Http::timeout(15)->get(
                'https://maps.googleapis.com/maps/api/place/findplacefromtext/json',
                [
                    'input' => urlencode($companyName . ' perusahaan Indonesia'),
                    'inputtype' => 'textquery',
                    'fields' => 'place_id', // Hanya request place_id
                    'region' => 'id',
                    'key' => $apiKey,
                ]
            );

            if (!$findPlaceResponse->successful()) {
                \Log::warning('Find Place API error: ' . $findPlaceResponse->body());
                return null;
            }

            $findPlaceData = $findPlaceResponse->json();

            // Cek jika ada place_id
            if ($findPlaceData['status'] !== 'OK' || empty($findPlaceData['candidates'])) {
                \Log::warning('No place found for: ' . $companyName . ' with perusahaan Indonesia'); // [MODIFIED]
                // [MODIFIED] Fallback: coba pencarian tanpa menambahkan "perusahaan Indonesia"
                $findPlaceResponse = Http::timeout(15)->get(
                    'https://maps.googleapis.com/maps/api/place/findplacefromtext/json',
                    [
                        'input' => urlencode($companyName), // [MODIFIED]
                        'inputtype' => 'textquery',
                        'fields' => 'place_id',
                        'region' => 'id',
                        'key' => $apiKey,
                    ]
                );
                if ($findPlaceResponse->successful()) { // [MODIFIED]
                    $findPlaceData = $findPlaceResponse->json(); // [MODIFIED]
                    if ($findPlaceData['status'] !== 'OK' || empty($findPlaceData['candidates'])) { // [MODIFIED]
                        \Log::warning('Fallback search failed for: ' . $companyName); // [MODIFIED]
                        return null;
                    }
                } else { // [MODIFIED]
                    return null; // [MODIFIED]
                }
            }

            $placeId = $findPlaceData['candidates'][0]['place_id'];

            // Step 2: Ambil detail menggunakan Place Details API
            $detailsResponse = Http::timeout(15)->get(
                'https://maps.googleapis.com/maps/api/place/details/json',
                [
                    'place_id' => $placeId,
                    'fields' => $fieldMap[$type], // Ambil field spesifik
                    'region' => 'id',
                    'key' => $apiKey,
                ]
            );

            if (!$detailsResponse->successful()) {
                \Log::warning('Place Details API error: ' . $detailsResponse->body());
                return null;
            }

            $detailsData = $detailsResponse->json();

            if ($detailsData['status'] === 'OK') {
                return $detailsData['result'][$fieldMap[$type]] ?? null;
            }

            \Log::warning('Place Details API error: ' . json_encode($detailsData));
            return null;

        } catch (\Exception $e) {
            \Log::error('Google Maps API failure: ' . $e->getMessage());
            return null;
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
            // [MODIFIED] Jika tidak ditemukan hasil, coba fallback dengan query yang lebih umum
            if (empty($data['items'])) {
                \Log::warning('No items found for query: ' . $queries[$type] . '. Trying fallback search.'); // [MODIFIED]
                $fallbackResponse = Http::get($url, [
                    'q' => $query, // [MODIFIED]
                    'key' => $apiKey,
                    'cx' => $searchEngineId,
                ]);
                if ($fallbackResponse->successful()) { // [MODIFIED]
                    $fallbackData = $fallbackResponse->json(); // [MODIFIED]
                    return $fallbackData['items'] ?? []; // [MODIFIED]
                }
            }
            return $data['items'] ?? [];
        }

        return null;
    }

    private function processUploadedFile($file)
    {
        try {
            $extension = $file->getClientOriginalExtension();
            $content = '';

            switch (strtolower($extension)) {
                case 'pdf':
                    $parser = new Parser();
                    $pdf = $parser->parseFile($file->path());
                    $content = $pdf->getText();
                    break;

                case 'doc':
                case 'docx':
                    $content = shell_exec("antiword -t " . escapeshellarg($file->path()));
                    break;

                case 'txt':
                    $content = file_get_contents($file->path());
                    break;
            }

            return \Illuminate\Support\Str::limit(strip_tags($content), 5000);

        } catch (\Exception $e) {
            \Log::error('File processing error: ' . $e->getMessage());
            return null;
        }
    }

    public function profileCompany(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'company_name' => 'required|string',
            'file' => 'nullable|file|max:5120|mimes:pdf,doc,docx,txt'
        ]);


        $companyName = $request->company_name;
        // AIController.php (di dalam method profileCompany)
        session(['companyName' => $request->company_name]);

        // Proses File
        // $fileContent = '';
        // if ($request->hasFile('file')) {
        //     $fileContent = $this->processUploadedFile($request->file('file')) ?? '';
        // }

        $fileContent = '';
        if ($request->hasFile('file')) {
            $fileContent = $this->processUploadedFile($request->file('file')) ?? 'Tidak ada informasi tambahan dari dokumen';
            session(['uploadedFileContent' => $fileContent]); // Update session dengan file baru
        } else {
            $fileContent = session('uploadedFileContent', 'Tidak ada informasi tambahan dari dokumen'); // Gunakan session jika tidak ada file baru
        }

        // 2. Cari perusahaan di database
        $company = DB::table('companies')
            ->where('name', 'LIKE', '%' . $companyName . '%')
            ->first();

        // [NEW MODIFIED] Buat cache key berdasarkan nama perusahaan (atau ID jika ada) agar output konsisten
        $cacheKey = 'company_profile_' . md5($company->name);
        if (Cache::has($cacheKey)) { // [NEW MODIFIED]
            $cachedProfile = Cache::get($cacheKey); // [NEW MODIFIED]
            return response()->json($cachedProfile); // [NEW MODIFIED]
        }

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
            'address' => $this->searchWithGoogleMaps($companyName, 'address'),
            'phone' => $this->searchWithGoogleMaps($companyName, 'phone'),
            'website' => $this->searchWithGoogleMaps($companyName, 'website'),
            // Kategori lainnya tetap menggunakan method lama
            'email' => $this->searchCompanyDetails($companyName, 'email'),
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
            if (in_array($key, ['address', 'phone', 'website'])) {
                $info = $detail ?? "";
            } else {
                if ($detail) {
                    $snippets = array_map(fn($item) => $item['snippet'] ?? '', $detail);
                    $info = implode("\n", array_filter($snippets));
                } else {
                    $info = "";
                }
            }
            $additionalInfo .= ucfirst($key) . ": " . ($info ? $info : "") . "\n";
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

        Profil Perusahaan

        Informasi Dasar Perusahaan
        Nama Perusahaan: {$company->name} [Tambahkan dari pencarian]
        Deskripsi Perusahaan: {$company->description}
        Jenis Industri: {$company->industry}

        Informasi Tambahan
        Alamat Kantor Pusat: [Lengkapi dari pencarian]
        Nomor Telepon: [Lengkapi dari pencarian]
        Alamat Email: [Lengkapi dari pencarian]
        Website Resmi: [Lengkapi dari pencarian]
        Tahun Berdiri: [Lengkapi dari pencarian]
        Bentuk Badan Usaha: Perseroan Terbatas
        Jumlah Karyawan: [Lengkapi dari pencarian atau buat rentang perkiraan]
        Struktur Manajemen: [Lengkapi dari pencarian]

        Produk dan Layanan
        [Lengkapi dari pencarian]

        Anak Perusahaan
        [Lengkapi dari pencarian]

        Relasi Perusahaan
        [Lengkapi dari pencarian]

        Informasi tambahan dari pencarian web:
        {$additionalInfo}

        Berita Terkait Perusahaan
        [Lengkapi dari pencarian]

        Rekomendasi Produk
        Dari data berikut, pilih produk yang sesuai dengan perusahaan:
        {$produkList}

        Informasi Tambahan dari Dokumen Upload: [Rangkum segala hal yang berkenaan dengan poin finanancialnya].
        " . ($fileContent ?: "Tidak ada informasi tambahan dari dokumen") . "

        ";
        $prompt .= "\nJika terdapat informasi yang kosong atau hanya berupa placeholder, silakan ciptakan data yang relevan, logis, dan konsisten berdasarkan konteks perusahaan ini. Pastikan bahwa jika perusahaan yang sama ditanyakan kembali, output yang dihasilkan harus sama persis dan hilangkan mark **.";
        $prompt .= "**Informasi dari Dokumen Upload:**\n";
        $prompt .= $fileContent ?: "Tidak ada informasi tambahan dari dokumen";

        // 5. Kirim permintaan ke OpenAI
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful business profiler. Provide detailed company profiles based on the given data and internet search in Indonesian. If any information is missing or incomplete, please invent plausible and logical details based on the company context. Ensure that the final output is consistent and deterministic for the given company (i.e. the same company queried multiple times should yield identical profiles). Do not include any placeholders like "[Lengkapi dari pencarian]" and Remove the ** mark in the final output.',
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
                'company_name' => $company->name,
                'profile' => $responseData['choices'][0]['message']['content'],
                'featured_employees' => $featuredEmployees,
                'similar_companies' => $similarCompanies,
                'file_content' => $fileContent
            ]);

            // [NEW MODIFIED] Simpan hasil output ke cache agar konsisten untuk perusahaan yang sama
            Cache::put($cacheKey, $result, now()->addDays(30));
            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Error with OpenAI: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memproses data dengan OpenAI: ' . $e->getMessage(),
            ], 500);
        }
    }

    // AIController.php
    public function exportPDF(Request $request)
    {
        // Ambil company_name dari query parameter
        $companyName = $request->input('company_name');
        if (!$companyName) {
            return redirect()->back()->with('error', 'Nama perusahaan tidak ditemukan');
        }

        // Gunakan session untuk mengambil konten file yang sebelumnya diunggah
        $fileContent = session('uploadedFileContent', 'Tidak ada informasi tambahan dari dokumen');

        // Gunakan logika dari profileCompany()
        $profileResponse = $this->profileCompany(new Request(['company_name' => $companyName]));

        if ($profileResponse->getStatusCode() !== 200) {
            return redirect()->back()->with('error', 'Gagal menghasilkan PDF');
        }

        $data = $profileResponse->getData(true);

        // Pastikan file content tetap ada
        $data['file_content'] = $fileContent;

        // Generate PDF
        $pdf = Pdf::loadView('pdf.company-profile', $data);
        return $pdf->download("profil-{$companyName}.pdf");
    }


}
