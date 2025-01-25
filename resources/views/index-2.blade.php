<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask BJB</title>
    <!-- Vite & Style -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
        rel="stylesheet">
</head>

<body class="h-screen flex flex-col">
    @include('components.navbar')
    @include('components.sidebar')

    <div class="flex flex-col h-full p-4 sm:ml-64">
        <!-- Panduan -->
        <div id="guide" class="mb-4">
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow text-center mb-[30px]">
                <p>Hai!, Saya adalah AskBjb, aplikasi yang dibuat menggunakan Generative Artificial Intelligence Index
                    duakkkkk
                    <br>
                    (jenis kecerdasan buatan yang dirancang untuk menghasilkan konten, data, atau informasi baru).
                </p>
            </div>
            <div class="grid md:grid-cols-2 gap-[12px] mb-4">
                <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
                    <p class="text-center font-semibold">Bantuan Pertanyaan yang Baik</p>
                    <ol class="ps-5 mt-2 space-y-1 list-decimal list-inside">
                        <li>Bagaimana cara untuk membuat rekening tandamata berjangka?</li>
                        <li>Apa resiko jika keluar dari pekerjaan?</li>
                        <li>Berapa banyak produk tabungan yang dimiliki bank bjb?</li>
                        <li>Bagaimana caranya mengajukan cuti?</li>
                        <li>Apakah perusahaan PT. Terus Maju telah memiliki kerja sama atau menjadi nasabah bank
                            bjb?</li>
                    </ol>
                </div>
                <div class="p-6 bg-white border border-gray-200 rounded-lg shadow text-center">
                    <p class="text-center font-semibold">Pertanyaan yang Kurang Tepat</p>
                    <ol class="text-left ps-5 mt-2 space-y-1 list-decimal list-inside">
                        <li>Bagaimana cara untuk membuat rekening tandamata?</li>
                        <li>Libur hari apa?</li>
                        <li>Berapa gaji saya?</li>
                        <li>Saya mau cuti?</li>
                        <li>Saya sakit?</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Chatbox -->
        <div id="chatBox" class="flex-grow p-4 rounded overflow-y-auto bg-gray-100">
        </div>

        <!-- Input & File Upload -->
        <div class="mt-4 md:flex items-center space-x-2 mb-4 w-full">
            <label for="file"
                class="file-label rounded w-full sm:w-32 mb-2 sm:mb-0 h-full flex items-center justify-center bg-gray-200 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="clip-icon w-5 h-5 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h9a2.25 2.25 0 002.25-2.25V15" />
                </svg>
                <input type="file" id="file" class="hidden" />
            </label>
            <div class="flex gap-2 w-full">
                <input type="text" id="companyName" name="company_name"
                    class="flex-grow p-4 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Nama Perusahaan" />
                <button type="submit" id="searchCompany"
                    class="p-4 bg-blue-700 text-white font-medium rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                    Kirim
                </button>
            </div>
        </div>

        <div id="errorContainer" class="text-red-500 mt-2"></div>
    </div>

    <!-- AXIOS -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        document.getElementById('add-chat').addEventListener('click', async () => {
            const addChat = document.getElementById('add-chat');
            const nowChat = document.getElementById('chats');
            const newChat = document.getElementById('new-chats');

            newChat.style.display = 'flex';
            newChat.style.display = '';
        })

        function setItemWithExpiry(key, value, expiryInSeconds) {
            const now = new Date().getTime();
            const item = {
                value: value,
                expires: now + expiryInSeconds * 1000, // Konversi ke milidetik
            };
            localStorage.setItem(key, JSON.stringify(item));
        }

        // Fungsi untuk mengambil data yang belum kedaluwarsa
        function getItemWithExpiry(key) {
            const itemStr = localStorage.getItem(key);
            if (!itemStr) return null;

            const item = JSON.parse(itemStr);
            const now = new Date().getTime();

            if (now > item.expires) {
                localStorage.removeItem(key); // Hapus jika sudah expired
                return null;
            }
            return item.value;
        }

        document.getElementById('searchCompany').addEventListener('click', async () => {
            const guideElement = document.getElementById('guide');
            const chatBoxElement = document.getElementById('chatBox');
            const companyName = document.getElementById('companyName').value;
            const fileInput = document.getElementById('file');
            const errorContainer = document.getElementById('errorContainer');
            const companyNameInput = document.getElementById('companyName');
            const companyNameDisplay1 = document.getElementById(
                'companyNameDisplay1'); // Pastikan ada elemen ini di HTML

            if (companyNameInput) {
                const companyName1 = companyNameInput.value;
                setItemWithExpiry('companyName1', companyName1, 1800);
                if (companyNameDisplay1) {
                    companyNameDisplay1.innerText = companyName1;
                }

                companyNameInput.value = "";
            }

            if (guideElement) guideElement.style.display = 'none';
            if (chatBoxElement) chatBoxElement.style.height = 'calc(100vh - 200px)';

            if (!companyName) {
                errorContainer.textContent = "Nama perusahaan wajib diisi.";
                return;
            } else {
                errorContainer.textContent = "";
            }

            addMessageToChat('user', `Perusahaan: ${companyName}`);
            const loadingId = addLoadingIndicator();

            const formData = new FormData();
            formData.append('company_name', companyName);
            if (fileInput.files[0]) {
                formData.append('file', fileInput.files[0]);
            }

            try {
                const response = await axios.post('/profile-company', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                });

                removeLoadingIndicator(loadingId);

                if (response.data.profile) {
                    const profileHTML = renderProfile(response.data);
                    addMessageToChat('assistant', profileHTML);
                } else {
                    addMessageToChat('assistant', response.data.error || 'Profil tidak tersedia.');
                }
            } catch (error) {
                console.error(error);
                removeLoadingIndicator(loadingId);
                addMessageToChat('assistant', error.response?.data?.error || 'Terjadi kesalahan.');
            }
        });

        function addMessageToChat(role, content) {
            const chatBox = document.getElementById('chatBox');
            const messageElement = document.createElement('div');
            messageElement.className = `chat-bubble ${role === 'user' ? 'user' : 'assistant'}`;
            messageElement.innerHTML = `
                <div class="chat-meta">${role === 'user' ? 'Anda' : 'Asisten'}</div>
                <div class="chat-content">${content.replace(/\n/g, '<br>')}</div>
            `;

            // Tambahkan elemen ke chatbox
            chatBox.appendChild(messageElement);

            // Scroll otomatis ke bagian bawah
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const storedCompanyName = getItemWithExpiry('companyName') || 'New Chat';
            document.getElementById('companyNameDisplay').innerText = storedCompanyName;
            const storedCompanyName1 = getItemWithExpiry('companyName1') || 'New Chat';
            document.getElementById('companyNameDisplay1').innerText = storedCompanyName1;
        });

        function addLoadingIndicator() {
            const chatBox = document.getElementById('chatBox');
            const loadingId = `loading-${Date.now()}`;
            const loadingElement = document.createElement('div');
            loadingElement.id = loadingId;
            loadingElement.className = 'text-left mb-2';
            loadingElement.innerHTML = `
                <span class="bg-gray-100 p-2 rounded block max-w-xl mx-2 flex items-center">
                    <span class="loading-spinner mr-2"></span> Sedang memproses...
                </span>`;
            chatBox.appendChild(loadingElement);
            chatBox.scrollTop = chatBox.scrollHeight;
            return loadingId;
        }

        function removeLoadingIndicator(loadingId) {
            const loadingElement = document.getElementById(loadingId);
            if (loadingElement) loadingElement.remove();
        }

        function renderProfile(data) {
            let html = '';
            if (data.company_name) {
                html += `<h2 class="font-bold text-lg">Profil Perusahaan: ${data.company_name}</h2>`;
            }
            if (data.profile) {
                html += `<div class="mt-2">${data.profile.replace(/\n/g, '<br>')}</div>`;
            }
            if (data.featured_employees?.length) {
                html += '<h3 class="mt-4 font-semibold">Karyawan Terkait</h3><ul class="list-disc list-inside">';
                data.featured_employees.forEach(emp => {
                    if (emp.url) {
                        html +=
                            `<li><a href="${emp.url}" target="_blank" class="text-blue-500 underline">${emp.url}</a></li>`;
                    }
                });
                html += '</ul>';
            }
            if (data.similar_companies?.length) {
                html += '<h3 class="mt-4 font-semibold">Perusahaan Serupa</h3><ul class="list-disc list-inside">';
                data.similar_companies.forEach(comp => {
                    if (comp.url) {
                        html +=
                            `<li><a href="${comp.url}" target="_blank" class="text-blue-500 underline">${comp.url}</a></li>`;
                    }
                });
                html += '</ul>';
            }
            return html;
        }

        document.getElementById('remove-chat').addEventListener('click', function() {
            window.location.href = "{{ route('ask-bjb') }}";
        });
    </script>
</body>

</html>
