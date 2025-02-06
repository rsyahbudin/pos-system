## Persiapan Project Kasir

1. Local Server Laragon 
2. Composer
3. Git
4. Node.js
5. php version >= 8.2

*Sisi eksternal*

1. Printer Thermal ukuran 58mm (Sambungkan printer ke komputer/laptop, jika belum terdaftar pada komputer/laptop maka install driver printer terlebih dahulu atau tonton video tutorial di youtube terkait masalah ini) setelah itu salin nama printer yang telah terdaftar pada komputer/laptop ke dalam menu setting printer pada web kasirnya.
    printer untuk via dekstop akan berjalan hanya pada server local komputer yang terhubung ke printer (tanpa windows print).
    printer untuk via mobile akan berjalan pada browser.

2. Scanner QR Code dengan Kameran maupun alat scanner (Opsional)

## Setup Project Kasir

Sangat untuk menjalankan atau mensetup project ini.

1. Buat database terlebih dahulu
    *silahkan import file sql yang ada di dalam project ini*
2. Konfigurasikan file .env dengan database yang telah dibuat
3. Buka terminal di direktori project
4. Jalankan perintah `php artisan storage:link`
5. Jalankan perintah `php artisan serve` untuk menjalankan server
6. Buka browser dan kunjungi link http://127.0.0.1:8000
7. Login dengan email (admin@gmail.com) dan password (password)
8. Jalankan perintah `php artisan queue:work` untuk menjalankan queue job yang ada di dalam project ini 
    untuk memproses notifikasi ekpor pemasukan dan pengeluaran


Aplikasi siap di gunakan....





