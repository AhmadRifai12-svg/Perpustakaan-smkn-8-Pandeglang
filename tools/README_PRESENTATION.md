# Instruksi: Generate PPTX untuk presentasi

1. Buka terminal dan pindah ke folder `tools`:

```bash
cd c:/xampp/htdocs/perpustakaan2/tools
```

2. Install dependency PHPPresentation via Composer:

```bash
composer require phpoffice/phppresentation
```

3. Setelah selesai, buka `presentasi.html` di browser melalui: `http://localhost/perpustakaan2/presentasi.html`.
   - Tekan tombol **Generate PPTX** di pojok kanan bawah untuk mengunduh file `.pptx` yang dibuat dari judul-judul seksi `h3` pada halaman.

Catatan:
- Script `generate_pptx.php` membuat slide sederhana berdasarkan tag `h3` pada `presentasi.html`.
- Untuk slide yang lebih lengkap (teks paragraf, gambar), skrip bisa dikembangkan lebih lanjut.
