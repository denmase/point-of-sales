# Point of Sales – Laravel, Inertia, React

> Sistem kasir modern untuk penjualan, customer management, hutang-piutang, dan workflow pembayaran dalam satu aplikasi. Cocok untuk toko yang butuh POS cepat, data pelanggan rapi, invoice siap cetak, sampai pencatatan piutang dan hutang supplier.

![POS Preview](public/media/revamp-pos.png "Point of Sales Preview")
<sub>_Preview utama modul kasir. Screenshot tambahan ada di bagian bawah._</sub>

## What's New

-   **Hutang & Piutang**: modul receivables untuk nota barang/piutang pelanggan dan payables untuk hutang supplier.
-   **Customer Wilayah Indonesia**: data pelanggan mendukung provinsi, kabupaten/kota, kecamatan, sampai desa.
-   **Manual Transfer Bank**: transaksi bisa dibuat dengan metode transfer bank manual dan dikonfirmasi setelah dana diterima.
-   **Manajemen Rekening Bank**: tambah, edit, aktif/nonaktifkan, dan urutkan rekening bank yang dipakai di pembayaran.
-   **Store Profile & Sales Target**: pengaturan profil toko, logo, kontak, kota toko, dan target penjualan bulanan.
-   **Shipping Label PDF**: cetak label pengiriman dari transaksi yang sudah dibuat.
-   **Hold Transaction & Customer History**: simpan keranjang sementara dan lanjutkan transaksi nanti.

## Kenapa Menarik?

-   **POS cepat & intuitif** untuk kasir: pencarian barcode, cart multi-item, diskon, shipping cost, dan perhitungan pembayaran.
-   **Payment workflow fleksibel**: tunai, transfer bank manual, Midtrans Snap, Xendit Invoice, dan pay later/piutang.
-   **Dokumen siap cetak**: invoice A4, thermal receipt 58mm/80mm, shipping label, PDF piutang, dan PDF hutang.
-   **Dashboard operasional**: statistik penjualan, profit, target bulanan, produk terlaris, low stock, slow moving, pelanggan terbaik, dan top lokasi.
-   **Role & permission granular** dengan Spatie Laravel Permission.

## Teknologi Inti

-   [Laravel 12](https://laravel.com)
-   [Inertia.js](https://inertiajs.com) + [React](https://react.dev)
-   [Tailwind CSS](https://tailwindcss.com)
-   [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
-   [Laravolt Indonesia](https://github.com/laravolt/indonesia) untuk data wilayah Indonesia
-   [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) untuk PDF
-   [picqer/php-barcode-generator](https://github.com/picqer/php-barcode-generator) untuk barcode invoice/dokumen
-   Integrasi payment gateway Midtrans & Xendit

## Fitur Utama

### POS & Transaksi

-   Scan/cari produk via barcode
-   Cart multi-item dengan update qty
-   Diskon transaksi
-   Shipping cost per transaksi
-   Hold transaction dan resume cart
-   Add customer langsung dari halaman kasir
-   Customer history langsung dari POS
-   Keyboard shortcuts untuk alur kasir cepat

### Customer & Wilayah

-   CRUD customer
-   Data pelanggan dengan alamat lengkap
-   Hierarki wilayah: provinsi, kabupaten/kota, kecamatan, desa
-   Top customer dan top lokasi dari data transaksi

### Pembayaran

-   **Tunai**: hitung bayar, kembalian, dan status lunas otomatis
-   **Transfer Bank Manual**: pilih rekening bank aktif, transaksi dibuat pending, lalu admin dapat melakukan konfirmasi pembayaran
-   **Midtrans Snap**: generate payment link dari transaksi
-   **Xendit Invoice**: generate invoice pembayaran eksternal
-   **Pay Later / Piutang**: transaksi dapat dicatat sebagai piutang pelanggan

### Hutang, Piutang, dan Supplier

-   **Receivables / Nota Barang**:
    pantau invoice piutang, sisa tagihan, due date, pembayaran parsial, status unpaid/partial/paid/overdue
-   **Payables / Hutang Supplier**:
    catat hutang supplier, nominal, jatuh tempo, pembayaran parsial, dan histori pelunasan
-   **Supplier Management**:
    simpan data supplier untuk kebutuhan pencatatan hutang

### Dokumen & Cetak

-   Invoice transaksi publik dan internal
-   PDF invoice transaksi
-   Thermal receipt 58mm dan 80mm
-   Shipping label / resi pengiriman
-   PDF piutang pelanggan
-   PDF hutang supplier

### Dashboard & Laporan

-   Ringkasan kategori, produk, customer, transaksi, revenue, dan profit
-   Trend revenue harian
-   Today sales dan today profit
-   Monthly sales target
-   Top products, low stock products, slow moving products
-   Laporan penjualan
-   Laporan profit

### Pengaturan & Akses

-   Store profile settings
-   Payment gateway settings
-   Bank account settings
-   Role, user, dan permission management

## Metode Pembayaran

| Metode | Keterangan |
| ------ | ---------- |
| Tunai | Pembayaran langsung lunas di kasir |
| Transfer Bank | Manual transfer ke rekening toko, perlu konfirmasi pembayaran |
| Midtrans | Membuat payment link Snap |
| Xendit | Membuat invoice pembayaran |
| Pay Later | Dicatat sebagai piutang pelanggan |

## Cara Menjalankan

```bash
git clone https://github.com/aryadwiputra/point-of-sales.git
cd point-of-sales
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev
php artisan serve
```

## Konfigurasi Setelah Instalasi

Setelah login sebagai admin, lakukan pengecekan beberapa konfigurasi berikut:

1. **Profil toko**
   lengkapi nama toko, logo, alamat, telepon, email, website, dan kota toko.
2. **Payment gateway**
   aktifkan metode pembayaran yang dipakai: tunai, transfer bank, Midtrans, atau Xendit.
3. **Rekening bank**
   tambahkan rekening bank aktif untuk kebutuhan transfer manual, pembayaran piutang, atau pembayaran hutang.
4. **Target penjualan**
   isi target bulanan agar dashboard bisa menampilkan progress penjualan dengan benar.

> Catatan: Midtrans dan Xendit bersifat opsional. Jika tidak dikonfigurasi, aplikasi tetap bisa berjalan dengan pembayaran tunai dan transfer bank manual.

## Konfigurasi Webhook Payment Gateway

Untuk Midtrans dan Xendit, payment link saja tidak cukup. Status transaksi akan tetap `pending` sampai callback webhook diterima aplikasi.

Checklist minimal:

1. Set `APP_URL` ke domain publik aplikasi, jangan `localhost`
2. Jalankan `php artisan migrate`
3. Buka menu payment settings dan salin webhook URL yang ditampilkan aplikasi
4. Paste URL tersebut ke dashboard Midtrans/Xendit
5. Untuk Xendit, isi `Callback Token` yang sama dengan token verifikasi di dashboard Xendit

Contoh konfigurasi environment:

```env
APP_URL=https://pos.example.com
XENDIT_CALLBACK_TOKEN=your-secure-callback-token
```

Endpoint yang dipakai aplikasi:

-   Midtrans: `/api/webhooks/midtrans`
-   Xendit: `/api/webhooks/xendit`

Catatan penting:

-   Jika `APP_URL` masih `http://localhost`, webhook dari Midtrans/Xendit tidak akan bisa menjangkau aplikasi.
-   Xendit webhook akan ditolak jika `X-CALLBACK-TOKEN` tidak cocok dengan token yang disimpan di aplikasi.
-   Midtrans webhook diverifikasi menggunakan signature key dari payload notification.

## Default Login

-   **Admin**: `arya@gmail.com` / `password`
-   **Kasir**: `cashier@gmail.com` / `password`

## Seeder & Data Contoh

-   Seeder bawaan akan membuat role, permission, user default, payment setting awal, dan sample data.
-   `SampleDataSeeder` menyiapkan kategori, produk, customer, dan transaksi contoh.
-   Sample data mencoba mengunduh gambar produk/kategori ke storage publik.

Jika ingin menjalankan ulang sample data:

```bash
php artisan db:seed --class=SampleDataSeeder
```

## Catatan Integrasi

-   Data wilayah customer memakai `laravolt/indonesia`.
-   PDF invoice/receipt/shipping/piutang/hutang dibuat dengan DomPDF.
-   Barcode dokumen dibuat secara dinamis untuk invoice dan nomor dokumen.
-   Public share invoice tersedia melalui URL share transaksi.

## Keyboard Shortcuts POS

| Shortcut | Aksi |
| -------- | ---- |
| `/` atau `F5` | Fokus pencarian produk |
| `Escape` | Clear search dan tutup modal |
| `F1` | Buka numpad |
| `F2` | Submit transaksi |
| `F4` | Tampilkan bantuan shortcuts |

## Pengujian

Contoh pengujian yang tersedia:

```bash
php artisan test --filter=TransactionFlowTest
```

Test ini mencakup alur transaksi utama seperti checkout tunai, tampilan halaman invoice, dan request payment link Midtrans. README ini tidak mengklaim seluruh modul baru sudah memiliki coverage test yang lengkap.

## Cuplikan Layar

### Modul Revamp

| Modul | Preview |
| ----- | ------- |
| Dashboard | ![Dashboard Revamp](public/media/revamp-dashboard.png) |
| Kasir / POS | ![POS Revamp](public/media/revamp-pos.png) |

### Modul Legacy / Dokumen

| Modul | Preview |
| ----- | ------- |
| Dashboard Lama | ![Dashboard Screenshot](public/media/readme-dashboard.png) |
| POS Lama | ![POS Screenshot](public/media/readme-pos.png) |
| Invoice | ![Invoice Screenshot](public/media/readme-invoice.png) |

<sub>_Disarankan menambahkan screenshot terbaru untuk modul piutang, hutang supplier, customer form wilayah, payment settings, dan bank accounts agar README lebih representatif._</sub>

## Struktur Modul Penting

-   `dashboard/transactions`: POS, checkout, print, hold cart
-   `dashboard/customers`: customer management + region data
-   `dashboard/receivables`: piutang pelanggan
-   `dashboard/payables`: hutang supplier
-   `dashboard/suppliers`: supplier management
-   `dashboard/settings/payments`: payment gateway settings
-   `dashboard/settings/bank-accounts`: rekening bank
-   `dashboard/settings/store`: profil toko

## Kontribusi

1. Fork repo ini
2. Buat branch fitur: `git checkout -b feature/namamu`
3. Commit perubahanmu: `git commit -m "Tambah fitur X"`
4. Push branch: `git push origin feature/namamu`
5. Buka Pull Request

## Authors

-   [Arya Dwi Putra](https://www.github.com/aryadwiputra)
-   Aplikasi ini menggunakan resource dari https://github.com/Raf-Taufiqurrahman/RILT-Starter dengan beberapa modifikasi untuk mendukung aplikasi kasir

## Dukung Proyek Ini

Kalau repositori ini membantumu membangun POS lebih cepat, klik **Star** agar proyek lebih mudah ditemukan developer lain.

---

Made with Laravel + React untuk kebutuhan Point of Sales modern.
