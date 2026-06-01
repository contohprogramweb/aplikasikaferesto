# Aplikasi Kaferesto

Sistem pemesanan restoran terintegrasi yang menghubungkan proses pemesanan dari pelanggan langsung ke dapur. Aplikasi ini dirancang untuk meningkatkan efisiensi operasional restoran dengan mengurangi waktu tunggu dan meminimalkan kesalahan pesanan.

## 📋 Daftar Isi

- [Fitur](#-fitur)
- [Prasyarat](#-prasyarat)
- [Instalasi](#-instalasi)
- [Penggunaan](#-penggunaan)
- [Struktur Proyek](#-struktur-proyek)
- [Konfigurasi](#-konfigurasi)
- [Testing](#-testing)
- [Deploy](#-deploy)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)
- [Kontak](#-kontak)

## ✨ Fitur

Fitur utama Aplikasi Kafresto:

- ✅ **Pemesanan Digital**: Pelanggan dapat melakukan pemesanan melalui interface digital
- ✅ **Integrasi Dapur**: Pesanan langsung terkirim ke sistem dapur secara real-time
- ✅ **Manajemen Menu**: Admin dapat mengelola menu, harga, dan ketersediaan item
- ✅ **Tracking Pesanan**: Status pesanan dapat dipantau dari pemesanan hingga penyajian
- ✅ **Multi-user Support**: Mendukung berbagai role (kasir, kitchen staff, manager)
- ✅ **Laporan & Analytics**: Dashboard untuk monitoring performa restoran

## 🛠 Prasyarat

Sebelum menjalankan aplikasi, pastikan Anda telah menginstal:

- [Node.js](https://nodejs.org/) (versi 18 atau lebih tinggi)
- [npm](https://www.npmjs.com/) atau [yarn](https://yarnpkg.com/)
- Database (MySQL/PostgreSQL/MongoDB - sesuaikan dengan implementasi)
- Git untuk version control

## 📦 Instalasi

Ikuti langkah-langkah berikut untuk menginstal aplikasi:

1. Clone repository ini:
```bash
git clone https://github.com/username/aplikasikafresto.git
cd aplikasikafresto
```

2. Instal dependensi:
```bash
npm install
# atau
yarn install
```

3. Salin file environment:
```bash
cp .env.example .env
```

4. Konfigurasi variabel environment di file `.env` sesuai kebutuhan Anda

5. Setup database (jika diperlukan):
```bash
npm run db:migrate
# atau
npm run db:seed
```

6. Jalankan aplikasi:
```bash
npm run dev
# atau
yarn dev
```

Aplikasi akan berjalan di `http://localhost:3000`

## 🚀 Penggunaan

### Untuk Kasir/Staff Frontend

1. Login menggunakan kredensial yang diberikan
2. Pilih meja atau buat pesanan baru
3. Tambahkan item menu ke pesanan
4. Kirim pesanan ke dapur
5. Monitor status pesanan

### Untuk Kitchen Staff

1. Login dengan role kitchen
2. Lihat daftar pesanan yang masuk secara real-time
3. Update status pesanan (Preparing, Ready, Served)
4. Kelola prioritas pesanan

### Untuk Manager/Admin

1. Akses dashboard untuk melihat analytics
2. Kelola menu (tambah, edit, hapus item)
3. Kelola user dan permissions
4. Generate laporan penjualan

## 📁 Struktur Proyek

```
aplikasikafresto/
├── src/
│   ├── components/       # Komponen UI reusable
│   ├── pages/           # Halaman-halaman aplikasi
│   ├── services/        # API services dan business logic
│   ├── utils/           # Fungsi utilitas dan helpers
│   ├── config/          # Konfigurasi aplikasi
│   ├── models/          # Database models
│   └── index.js         # Entry point aplikasi
├── public/              # File statis (images, fonts, dll)
├── tests/               # Test files
├── docs/                # Dokumentasi tambahan
├── .env.example         # Contoh konfigurasi environment
├── .gitignore          # Git ignore rules
├── package.json        # Dependencies dan scripts
└── README.md           # Dokumentasi ini
```

## ⚙️ Konfigurasi

Konfigurasi aplikasi melalui file `.env`:

| Variabel | Deskripsi | Default |
|----------|-----------|---------|
| `PORT` | Port server aplikasi | `3000` |
| `DATABASE_URL` | URL koneksi database | - |
| `API_KEY` | API key untuk integrasi eksternal | - |
| `NODE_ENV` | Environment (development/production) | `development` |
| `SESSION_SECRET` | Secret key untuk session | - |

## 🧪 Testing

Jalankan test suite untuk memastikan aplikasi berfungsi dengan baik:

```bash
# Menjalankan semua test
npm test

# Menjalankan test dengan watch mode
npm run test:watch

# Cek test coverage
npm run test:coverage
```

## 🌐 Deploy

### Menggunakan Docker

```bash
# Build Docker image
docker build -t aplikasikafresto .

# Run container
docker run -p 3000:3000 aplikasikafresto
```

### Deploy ke Production

Panduan deploy ke berbagai platform:

**Heroku:**
```bash
heroku create aplikasikafresto
git push heroku main
```

**Vercel/Netlify:**
```bash
npm run build
# Follow platform-specific deployment instructions
```

## 🤝 Kontribusi

Kami sangat menyambut kontribusi dari komunitas! Cara berkontribusi:

1. Fork proyek ini
2. Buat branch fitur baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan Anda (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buka Pull Request

Pastikan untuk membaca [CONTRIBUTING.md](CONTRIBUTING.md) untuk panduan detail.

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).

## 📞 Kontak

- **Developer**: Tim Kafresto
- **Email**: support@kafresto.com
- **Website**: https://kafresto.com

## 🙏 Ucapan Terima Kasih

Terima kasih kepada:
- Semua kontributor yang telah membantu pengembangan
- Komunitas open source yang menyediakan library dan tools
- User yang memberikan feedback berharga

---

**Made with ❤️ by Tim Kaferesto**
