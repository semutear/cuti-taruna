// Util UI bersama: toast, confirm dialog, label status/transportasi, empty & loading state tabel.
// Dipakai di semua halaman dashboard sebagai pengganti alert()/confirm() bawaan browser.

function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const icons = { success: 'fa-check-circle', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info} mt-0.5"></i><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 200);
    }, 3500);
}

function confirmDialog(message) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'confirm-overlay';
        overlay.innerHTML = `
            <div class="confirm-box">
                <p class="text-gray-800 mb-5">${message}</p>
                <div class="flex justify-end gap-2">
                    <button class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition" data-action="cancel">Batal</button>
                    <button class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition" data-action="ok">Ya, lanjutkan</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay || e.target.dataset.action === 'cancel') {
                overlay.remove();
                resolve(false);
            } else if (e.target.dataset.action === 'ok') {
                overlay.remove();
                resolve(true);
            }
        });
    });
}

const STATUS_LABELS = {
    pending: 'Menunggu',
    disetujui_ortu: 'Disetujui Orang Tua',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
};

const STATUS_BADGE_CLASSES = {
    pending: 'bg-amber-100 text-amber-800',
    disetujui_ortu: 'bg-blue-100 text-blue-800',
    disetujui: 'bg-green-100 text-green-800',
    ditolak: 'bg-red-100 text-red-800',
};

function labelStatus(status) {
    return STATUS_LABELS[status] || status;
}

function statusBadgeClass(status) {
    return STATUS_BADGE_CLASSES[status] || 'bg-gray-100 text-gray-800';
}

const TRANSPORTASI_LABELS = {
    kereta: 'Kereta Api',
    pesawat: 'Pesawat',
    bus: 'Bus',
    travel: 'Travel',
    ojol: 'Ojek Online',
    pribadi: 'Kendaraan Pribadi',
};

function labelTransportasi(t) {
    return TRANSPORTASI_LABELS[t] || t || '-';
}

function renderTableLoading(tbody, colspan) {
    tbody.innerHTML = `
        <tr class="table-state-row">
            <td colspan="${colspan}">
                <span class="table-spinner"></span>
                <p class="mt-2 text-sm">Memuat data...</p>
            </td>
        </tr>
    `;
}

function renderEmptyState(tbody, colspan, message = 'Belum ada data.') {
    tbody.innerHTML = `
        <tr class="table-state-row">
            <td colspan="${colspan}">
                <i class="fas fa-inbox text-3xl text-gray-300"></i>
                <p class="mt-2 text-sm">${message}</p>
            </td>
        </tr>
    `;
}

function renderErrorState(tbody, colspan, message = 'Gagal memuat data. Coba muat ulang halaman.') {
    tbody.innerHTML = `
        <tr class="table-state-row">
            <td colspan="${colspan}">
                <i class="fas fa-triangle-exclamation text-3xl text-red-300"></i>
                <p class="mt-2 text-sm text-red-500">${message}</p>
            </td>
        </tr>
    `;
}

// Sidebar drawer mobile
function initSidebarToggle() {
    const sidebar = document.querySelector('.app-sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    if (!sidebar || !toggleBtn) return;

    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);

    function close() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('open');
    }

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('open');
    });
    backdrop.addEventListener('click', close);
}
