# POS Mobile App - React Native

Aplikasi mobile Point of Sales yang terintegrasi dengan backend Laravel.

## 📱 Fitur

- **Login/Logout** - Autentikasi pengguna dengan token
- **Point of Sales** - Tampilan produk, pencarian, dan scan barcode
- **Keranjang Belanja** - Kelola item, qty, diskon, dan ongkir
- **Checkout** - Proses transaksi dengan berbagai metode pembayaran
- **Offline Support** - Cart disimpan di AsyncStorage

## 🚀 Cara Menjalankan

### Prerequisites

- Node.js >= 18
- npm atau yarn
- Expo CLI (opsional)
- Android Studio / Xcode (untuk emulator)

### Instalasi

```bash
cd pos-mobile

# Install dependencies
npm install

# Jalankan development server
npm start

# Atau jalankan langsung ke device/emulator
npm run android  # Android
npm run ios      # iOS (macOS only)
npm run web      # Web browser
```

### Konfigurasi API

Edit file `src/services/api.ts` dan ubah `API_BASE_URL` sesuai dengan IP server Laravel Anda:

```typescript
const API_BASE_URL = 'http://192.168.1.100:8000/api';
```

> **Catatan:** Gunakan IP lokal komputer Anda, bukan localhost, jika ingin mengakses dari device fisik.

## 🏗️ Struktur Folder

```
pos-mobile/
├── src/
│   ├── components/     # Reusable components
│   ├── context/        # React Context (Cart, Auth, dll)
│   ├── navigation/     # React Navigation setup
│   ├── screens/        # Screen components
│   │   ├── LoginScreen.tsx
│   │   ├── HomeScreen.tsx
│   │   ├── POSScreen.tsx
│   │   └── CartScreen.tsx
│   ├── services/       # API services
│   │   └── api.ts
│   ├── types/          # TypeScript types
│   │   └── index.ts
│   └── utils/          # Helper functions
├── App.tsx             # Main app component
├── app.json            # Expo configuration
└── package.json
```

## 🔐 Default Login

- **Admin**: arya@gmail.com / password
- **Kasir**: cashier@gmail.com / password

## 🛠️ Tech Stack

- **React Native** with Expo
- **TypeScript**
- **React Navigation** - Navigation
- **Axios** - HTTP Client
- **AsyncStorage** - Local storage

## 📲 Build untuk Production

### Android APK

```bash
# Install EAS CLI
npm install -g eas-cli

# Configure EAS
eas build:configure

# Build APK
eas build --platform android --profile preview
```

### iOS

```bash
# Build untuk iOS
eas build --platform ios
```

## 🔗 Integrasi dengan Backend Laravel

Pastikan backend Laravel Anda memiliki endpoint berikut:

- `POST /api/login` - Login user
- `GET /api/products` - List produk
- `GET /api/products/search?q=` - Cari produk
- `GET /api/products/barcode/{barcode}` - Get produk by barcode
- `GET /api/customers` - List pelanggan
- `POST /api/customers` - Tambah pelanggan
- `POST /api/transactions` - Buat transaksi
- `GET /api/transactions` - List transaksi
- `GET /api/transactions/{id}` - Detail transaksi

## 📝 Catatan Development

1. **Hot Reload**: Tekan `r` di terminal untuk reload manual
2. **Developer Menu**: Shake device atau tekan `Cmd+D` (iOS) / `Cmd+M` (Android)
3. **Debugging**: Gunakan React Native Debugger atau Chrome DevTools

## 🤝 Kontribusi

1. Fork repository ini
2. Buat branch fitur: `git checkout -b feature/namamu`
3. Commit perubahan: `git commit -m "Tambah fitur X"`
4. Push branch: `git push origin feature/namamu`
5. Buka Pull Request

## 📄 License

MIT License

---

Made with ❤️ using React Native & Expo
