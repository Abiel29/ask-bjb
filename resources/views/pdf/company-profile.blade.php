<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Perusahaan - {{ $company_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h1 {
            color: #2c3e50;
            text-align: center;
        }

        h2 {
            color: #34495e;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .section-content {
            margin-left: 10px;
            line-height: 1.6;
            white-space: pre-line;
        }

        .employee-list,
        .company-list,
        .product-list {
            list-style-type: disc;
            margin-left: 20px;
        }

        .product-item {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <h1>Profil Perusahaan: {{ $company_name }}</h1>

    <!-- Profil Perusahaan -->
    <div class="section">
        <div class="section-title">Profil Perusahaan</div>
        <div class="section-content">{{ $profile }}</div>
    </div>

    <!-- Informasi Tambahan -->
    @if (!empty($additional_details))
        <div class="section">
            <div class="section-title">Informasi Tambahan</div>
            <div class="section-content">
                @foreach ($additional_details as $key => $detail)
                    <p><strong>{{ ucfirst($key) }}:</strong> {{ $detail }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Karyawan Terkait -->
    @if (!empty($featured_employees))
        <div class="section">
            <div class="section-title">Karyawan Terkait</div>
            <ul class="employee-list">
                @foreach ($featured_employees as $employee)
                    <li>{{ $employee['url'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Perusahaan Serupa -->
    @if (!empty($similar_companies))
        <div class="section">
            <div class="section-title">Perusahaan Serupa</div>
            <ul class="company-list">
                @foreach ($similar_companies as $company)
                    <li>{{ $company['url'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Deskripsi Produk -->
    @if (!empty($products) && is_array($products))
        <div class="section">
            <div class="section-title">Deskripsi Produk</div>
            <ul class="product-list">
                @foreach ($products as $product)
                    <li class="product-item">
                        <strong>Nama Produk:</strong> {{ $product['nama_produk'] ?? 'Tidak tersedia' }}<br>
                        <strong>Deskripsi:</strong> {{ $product['deskripsi'] ?? 'Tidak tersedia' }}<br>
                        <strong>Plafon:</strong> {{ $product['plafon'] ?? 'Tidak tersedia' }}<br>
                        <strong>Interest Rate:</strong> {{ $product['interest_rate'] ?? 'Tidak tersedia' }}<br>
                        <strong>Benefits:</strong>
                        <ul>
                            @foreach (explode("\n", trim($product['benefits'] ?? '')) as $benefit)
                                <li>{{ trim($benefit) }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</body>

</html>