/**
 * Format mata uang (IDR) dengan menggunakan lokal Indonesia.
 * @param {number} value - Nilai yang akan diformat
 * @returns {string} - Nilai dalam format IDR
 */
export const formatCurrency = (value) => {
    const num = parseFloat(value);

    if (isNaN(num)) return 'Rp 0';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0 
    }).format(num);
};

/**
 * Format tanggal dengan format yang mudah dibaca.
 * @param {string} dateString - String tanggal yang akan diformat
 * @returns {string} - Tanggal dalam format yang lebih mudah dibaca
 */
export const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};
