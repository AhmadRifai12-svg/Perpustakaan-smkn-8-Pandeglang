// JavaScript for pembayaran page

function togglePaymentInfo() {
    const metode = document.getElementById('metodePembayaran').value;
    const dokuInfo = document.getElementById('dokuInfo');
    const qrisInfo = document.getElementById('qrisInfo');
    const tunaiInfo = document.getElementById('tunaiInfo');

    dokuInfo.classList.add('hidden');
    qrisInfo.classList.add('hidden');
    tunaiInfo.classList.add('hidden');

    if (metode.startsWith('doku_')) {
        dokuInfo.classList.remove('hidden');
    } else if (metode === 'qris') {
        qrisInfo.classList.remove('hidden');
    } else if (metode === 'tunai') {
        tunaiInfo.classList.remove('hidden');
    }
}

// form submission handler
function onPembayaranSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const jumlah = parseInt(form.querySelector('input[name="jumlah_pembayaran"]').value);
    const total = parseInt(form.querySelector('input[name="total_denda"]').value);
    const metode = form.querySelector('select[name="metode_pembayaran"]').value;

    if (jumlah <= 0) {
        Swal.fire('Error!', 'Jumlah pembayaran harus lebih dari 0', 'error');
        return;
    }
    if (jumlah > total) {
        Swal.fire('Error!', 'Jumlah pembayaran tidak boleh melebihi total denda', 'error');
        return;
    }
    if (!metode) {
        Swal.fire('Error!', 'Metode pembayaran harus dipilih', 'error');
        return;
    }

    if (metode.startsWith('doku_')) {
        handleDOKUPayment(jumlah, metode);
        return;
    }

    const formData = new FormData(form);

    fetch('proses_pembayaran.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                let htmlContent = data.message;

                if (metode === 'qris') {
                    htmlContent += '<br><br><strong>✅ Pembayaran Otomatis!</strong><br><small>Buku akan dikembalikan secara otomatis setelah pembayaran diverifikasi.</small>';
                } else {
                    htmlContent += '<br><br><strong>Status: ⏳ Menunggu Verifikasi Admin</strong><br><small>Admin akan mengonfirmasi pembayaran Anda dalam 1-24 jam.</small>';
                }

                Swal.fire({
                    icon: data.auto_return ? 'success' : 'info',
                    title: data.auto_return ? '✅ Pembayaran Otomatis Berhasil!' : '✅ Pembayaran Dikirim',
                    html: htmlContent,
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'pembayaran.php';
                });
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error!', 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.', 'error');
        });
}

function handleDOKUPayment(jumlah, metode) {
    Swal.fire({
        title: 'Memproses Pembayaran DOKU...',
        html: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const paymentData = {
        jumlah_pembayaran: jumlah,
        metode_pembayaran: metode,
        total_denda: jumlah
    };

    fetch('doku_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(paymentData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.simulation && data.data.payment_url) {
                    Swal.fire({
                        icon: 'info',
                        title: '🔧 Mode Simulasi',
                        html: 'Anda akan diarahkan ke halaman simulasi DOKU untuk testing.',
                        confirmButtonText: 'Lanjut'
                    }).then(() => {
                        window.location.href = data.data.payment_url;
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Pembayaran DOKU Berhasil',
                        html: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'pembayaran.php';
                    });
                }
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error!', 'Terjadi kesalahan saat memproses pembayaran DOKU.', 'error');
        });
}

// listen to form once DOM has loaded
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formPembayaran');
    if (form) {
        form.addEventListener('submit', onPembayaranSubmit);
    }

    const metodeSelect = document.getElementById('metodePembayaran');
    if (metodeSelect) {
        metodeSelect.addEventListener('change', togglePaymentInfo);
    }
});