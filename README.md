# Coupon Panel - Sistem Manajemen Kupon

Sistem panel manajemen kupon lengkap dengan fitur login, CRUD kupon, approval system, dan reporting yang komprehensif.

## 📋 Fitur Utama

### 🔐 Autentikasi
- Login system dengan session management
- Logout functionality
- Middleware untuk proteksi halaman

### 📊 Dashboard
- Statistik kupon (total, approved, pending, expired)
- Overview kupon terbaru
- Quick access ke semua menu

### ➕ Management Kupon
- **Buat Kupon**: Form lengkap untuk membuat kupon baru
- **Approve Kupon**: System approval untuk kupon pending
- **Hasil Kupon**: View semua kupon dengan filter dan search
- **Laporan**: Report berbasis tanggal dengan chart dan export CSV

### 📈 Reporting System
- **Laporan Penggunaan**: Track usage kupon dengan grafik
- **Laporan Kupon Hangus**: Monitor expired coupons
- **Performance Report**: Analisis performa kupon
- **Export CSV**: Export data ke CSV

## 🗂️ Struktur File

```
/coupon-panel/
│── index.php          # Login Page
│── dashboard.php      # Dashboard utama  
│── create_coupon.php  # Form buat kupon
│── approve_coupon.php # Approve/reject kupon
│── result.php         # View hasil kupon
│── report.php         # Laporan dengan chart
│── logout.php         # Logout functionality
│── db.php             # Database connection
│── auth.php           # Authentication middleware
│── style.css          # Complete styling
│── demo_usage.php     # Demo usage simulator
│── coupon_panel.sql   # Database schema
└── README.md          # Dokumentasi
```

## 🚀 Instalasi dari GitHub

### 1. Clone Repository
```bash
git clone https://github.com/username/coupon-panel.git
cd coupon-panel
```

### 2. Persiapan Database
```sql
-- Buat database
CREATE DATABASE coupon_panel;

-- Import schema dari coupon_panel.sql
mysql -u root -p coupon_panel < coupon_panel.sql
```

### 2. Konfigurasi Database
Edit file `db.php` sesuai dengan setting database Anda:

```php
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "coupon_panel";
```

### 3. Upload Files
Upload semua file ke web server Anda (XAMPP, WAMP, atau hosting).

### 4. Set Permissions
Pastikan folder memiliki permission yang tepat:
```bash
chmod 755 /path/to/coupon-panel/
chmod 644 /path/to/coupon-panel/*.php
chmod 644 /path/to/coupon-panel/*.css
```

## 👤 Login Demo

**Username**: `admin`  
**Password**: `password`

## 💡 Cara Penggunaan

### 1. Login
- Akses `index.php` 
- Login dengan kredensial demo
- Akan redirect ke dashboard

### 2. Membuat Kupon
- Pilih menu "Buat Kupon"
- Isi form lengkap:
  - Kode kupon (unik)
  - Judul dan deskripsi
  - Jenis diskon (percentage/fixed)
  - Nilai diskon
  - Minimal order (optional)
  - Maksimal penggunaan
  - Periode aktif
- Submit untuk membuat kupon dengan status "pending"

### 3. Approve Kupon
- Menu "Approve Kupon" 
- Review kupon pending
- Klik "Approve" atau "Tolak"
- Monitor penggunaan kupon terbaru

### 4. Monitoring Hasil
- Menu "Hasil Kupon"
- Filter berdasarkan status
- Search berdasarkan kode/judul
- Lihat usage statistics dengan progress bar

### 5. Generate Laporan
- Menu "Laporan"
- Pilih jenis report:
  - **Usage Report**: Penggunaan kupon dengan chart
  - **Expired Report**: Kupon yang hangus
  - **Performance Report**: Analisis performa
- Set tanggal range
- View data atau export CSV

## 🔧 Kustomisasi

### Menambah User Admin Baru
```sql
-- Hash password menggunakan PHP
INSERT INTO users (username, password, email) VALUES 
('newadmin', '$2y$10$hashedpassword', 'admin@domain.com');
```

### Simulasi Usage (Demo)
Jalankan script demo untuk simulasi penggunaan kupon:
```bash
php demo_usage.php
```

### Custom Styling
Edit `style.css` untuk mengubah tampilan:
- Colors: Ubah variabel warna di bagian atas
- Layout: Modify grid dan flexbox properties  
- Components: Customize card, button, table styles

## 📊 Database Schema

### Tables:
- **users**: Admin users
- **coupons**: Master data kupon
- **coupon_usage**: Log penggunaan kupon

### Key Features:
- Foreign key relationships
- Auto-update expired coupons
- Usage tracking
- Approval workflow

## 🔒 Security Features

- **Password Hashing**: Menggunakan PHP password_hash()
