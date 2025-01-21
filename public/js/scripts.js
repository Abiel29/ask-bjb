document.getElementById('send').addEventListener('click', async () => {
    const prompt = document.getElementById('prompt').value;
    const fileInput = document.getElementById('file');
    const file = fileInput.files[0];

    if (!prompt && !file) {
        alert("Masukkan instruksi atau pilih file terlebih dahulu!");
        return;
    }

    // Tampilkan pesan user di chatbox
    if (prompt) {
        addMessageToChat('user', prompt);
    }
    if (file) {
        addMessageToChat('user-file', file.name);
    }

    // Tambahkan animasi loading ke chatbox
    const loadingId = addLoadingIndicator();

    const formData = new FormData();
    formData.append('prompt', prompt);
    if (file) {
        formData.append('file', file);
    }

    try {
        // Kirim permintaan ke API Laravel
        const response = await axios.post('/chat-upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        // Hapus animasi loading
        removeLoadingIndicator(loadingId);

        // Tampilkan respons AI di chatbox
        addMessageToChat('assistant', response.data.reply);

        // Bersihkan input
        document.getElementById('prompt').value = '';
        document.getElementById('file').value = '';
    } catch (error) {
        console.error(error);

        // Hapus animasi loading
        removeLoadingIndicator(loadingId);

        // Tampilkan pesan error di chatbox
        addMessageToChat('assistant', 'Terjadi kesalahan saat memproses permintaan.');
    }
});

document.getElementById('searchCompany').addEventListener('click', async () => {
    const companyName = document.getElementById('companyName').value;

    if (!companyName) {
        alert("Masukkan nama perusahaan terlebih dahulu!");
        return;
    }

    addMessageToChat('user', `Profilkan perusahaan: ${companyName}`);
    const loadingId = addLoadingIndicator();

    try {
        const response = await axios.post('/profile-company', { company_name: companyName });

        removeLoadingIndicator(loadingId);
        addMessageToChat('assistant', response.data.profile);
    } catch (error) {
        removeLoadingIndicator(loadingId);
        addMessageToChat('assistant', error.response?.data?.error || 'Terjadi kesalahan.');
    }
});


// Fungsi untuk menambahkan pesan ke chatbox
function addMessageToChat(role, content) {
    const chatBox = document.getElementById('chatBox');
    const messageElement = document.createElement('div');

    if (role === 'user-file') {
        messageElement.className = 'text-right mb-2';
        messageElement.innerHTML = `
            <span class="bg-blue-100 p-2 rounded block max-w-xl mx-2">
                📁 <strong>File:</strong> ${content}
            </span>
        `;
    } else {
        messageElement.className = role === 'user' ? 'text-right mb-2' : 'text-left mb-2';
        messageElement.innerHTML = `
            <span class="${role === 'user' ? 'bg-blue-100' : 'bg-gray-100'} p-2 rounded block max-w-xl mx-2"}>
                ${content}
            </span>
        `;
    }

    chatBox.appendChild(messageElement);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Fungsi untuk menambahkan animasi loading
function addLoadingIndicator() {
    const chatBox = document.getElementById('chatBox');
    const loadingId = `loading-${Date.now()}`;
    const loadingElement = document.createElement('div');
    loadingElement.id = loadingId;
    loadingElement.className = 'text-left mb-2';
    loadingElement.innerHTML = `
        <span class="bg-gray-100 p-2 rounded block max-w-xl mx-2 flex items-center">
            <span class="loading-spinner mr-2"></span> Sedang memproses...
        </span>
    `;
    chatBox.appendChild(loadingElement);
    chatBox.scrollTop = chatBox.scrollHeight;
    return loadingId;
}

// Fungsi untuk menghapus animasi loading
function removeLoadingIndicator(loadingId) {
    const loadingElement = document.getElementById(loadingId);
    if (loadingElement) {
        loadingElement.remove();
    }
}
