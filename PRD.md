# PRD — Sistem Pengelolaan Cuti Taruna

**Versi:** 0.1 (draft untuk direview)
**Tanggal:** 3 Juli 2026
**Penyusun:** Atika Rahma, Muhammad Rizq Dewangga, Michael Ridho
**Konteks:** Project UAS Pemrograman Lanjutan + bahan uji keamanan (static & dynamic analysis / white-hat testing)
**Stack:** Laravel 12 (REST API) · HTML/CSS/JS + Tailwind (web) · Kotlin/Android (mobile) · MySQL · SQLite (offline mobile)

---

## 1. Ringkasan Produk

Sistem Pengelolaan Cuti Taruna adalah aplikasi terintegrasi (web + mobile) yang mendigitalisasi proses pengajuan, persetujuan, dan monitoring cuti taruna di Politeknik Siber dan Sandi Negara. Satu backend REST API melayani dua klien (web dan Android) dengan tiga peran: **Taruna**, **Orang Tua**, dan **Pengasuh/Admin**.

### 1.1 Masalah yang Diselesaikan
- Pengajuan cuti manual (kertas) → dokumen hilang, lambat, sulit dilacak.
- Orang tua sulit memberi persetujuan tanpa datang ke kampus.
- Pengasuh sulit memonitor & merekap status cuti.

### 1.2 Tujuan Terukur (Success Metrics)
| Tujuan | Metrik |
|---|---|
| Pengajuan cuti digital | 100% pengajuan lewat sistem, tanpa kertas |
| Persetujuan cepat | Waktu rata-rata pending→diproses < 24 jam |
| Transparansi status | Taruna & ortu dapat melihat status real-time |
| Keamanan (UAS goal) | Nol temuan **Critical/High** tersisa setelah remediasi SAST/DAST |

### 1.3 Status Implementasi Saat Ini (baseline)
| Komponen | Status |
|---|---|
| REST API (Laravel + Sanctum) | ✅ Sudah ada (Auth, Cuti, Admin controller) |
| Web frontend (index, login, register, 3 dashboard) | ✅ Sudah ada |
| Database MySQL + migrasi (users, cuti_applications) | ✅ Sudah ada |
| Mobile Android (Kotlin) | ⛔ Belum ada (folder `mobile-app/` belum dibuat) — **masuk scope UAS (Must)** |
| Fitur statistik admin | ⚠️ Route ada (`/admin/statistics`), perlu verifikasi implementasi |
| Rate limiting / throttle login | ⚠️ Perlu diverifikasi/ditambah |
| Tanggal cuti (mulai/selesai) | ⛔ Belum ada — **akan ditambahkan (Must)** |
| Approval 2 tahap (ortu→pengasuh) | ⛔ Saat ini 1 tahap — **akan diubah (Must)** |

### 1.4 Keputusan Kunci (hasil review 4 Juli 2026)
1. **Mobile dibangun** (Android Kotlin penuh: Retrofit + Room offline sync).
2. **Strategi dua versi** (rentan & hardened) di GitHub, disinkronkan — lihat §11.
3. **Tambah field tanggal cuti** (mulai & selesai) + validasi durasi.
4. **Alur approval dua tahap:** Orang Tua menyetujui dulu → Pengasuh/Admin finalisasi.

---

## 2. Pengguna & Peran (Personas)

| Peran | Deskripsi | Cara Autentikasi (saat ini) |
|---|---|---|
| **Taruna** | Mahasiswa yang mengajukan cuti | `NPM + password` |
| **Orang Tua** | Menyetujui/menolak cuti anak (approval tahap 1) | Versi `vuln`: `nama_ibu + nama_anak + tgl_lahir_anak` (tanpa password). Versi `secure`: **username/identifier + password** |
| **Pengasuh (Admin)** | Monitoring penuh, statistik, finalisasi (approval tahap 2) | Versi `vuln`: `password` tunggal. Versi `secure`: **username + password kuat** |

> **Keputusan auth (versi `secure`):** SEMUA akun memakai password (taruna, orang tua, admin). Admin & orang tua memakai username/identifier + password, bukan data yang mudah ditebak. Versi `vuln` mempertahankan desain lemah sebagai objek eksploitasi. Detail di §8 & Lampiran A.

---

## 3. Functional Requirements

Setiap requirement diberi ID (`FR-xx`) agar bisa dilacak ke test case.

### 3.1 Autentikasi & Akun
| ID | Requirement | Prioritas |
|---|---|---|
| FR-01 | Taruna dapat registrasi (nama, NPM, nama ibu, tanggal lahir, password) | Must |
| FR-02 | Taruna login dengan NPM + password, menerima token Sanctum | Must |
| FR-03 | Orang tua login untuk mengakses data cuti anak | Must |
| FR-04 | Admin login untuk akses monitoring | Must |
| FR-05 | Logout menghapus token akses aktif | Must |
| FR-06 | Token wajib pada semua endpoint terproteksi | Must |

### 3.2 Pengajuan Cuti (Taruna)
| ID | Requirement | Prioritas |
|---|---|---|
| FR-10 | Taruna mengisi form cuti: alamat lengkap (jalan, RT/RW, kelurahan, kecamatan, kota, provinsi) | Must |
| FR-11 | Memilih tujuan: `orang_tua` atau `kerabat` (kerabat → wajib nama & nomor kerabat) | Must |
| FR-12 | Memilih transportasi: kereta/pesawat/bus/travel/ojol/pribadi | Must |
| FR-13 | Upload tiket wajib untuk transportasi kereta/pesawat/bus/travel (PDF/JPG/PNG, ≤2MB) | Must |
| FR-14 | Melihat riwayat cuti sendiri beserta status | Must |
| FR-15 | Mengubah/menghapus pengajuan yang masih `pending` | Should |
| FR-16 | Menentukan `tanggal_mulai` & `tanggal_selesai` cuti; sistem hitung durasi (hari); validasi `selesai ≥ mulai` & `mulai ≥ hari ini` | Must |

### 3.3 Persetujuan — Alur Dua Tahap (Orang Tua → Pengasuh)
Status cuti mengalir: `pending` → `disetujui_ortu` → `disetujui` (final oleh pengasuh). Penolakan di tahap mana pun → `ditolak`.

| ID | Requirement | Prioritas |
|---|---|---|
| FR-20 | Orang tua melihat daftar cuti anaknya | Must |
| FR-21 | **Tahap 1:** Orang tua menyetujui/menolak cuti `pending` → status jadi `disetujui_ortu` / `ditolak` | Must |
| FR-22 | Admin/Pengasuh melihat **seluruh** cuti semua taruna | Must |
| FR-23 | **Tahap 2:** Pengasuh finalisasi cuti berstatus `disetujui_ortu` → `disetujui` / `ditolak` | Must |
| FR-24 | Cuti hanya dapat difinalisasi pengasuh jika sudah `disetujui_ortu` (tidak bisa lompat tahap) | Must |
| FR-25 | Admin melihat statistik (pending/disetujui_ortu/disetujui/ditolak, per bulan) | Should |
| FR-26 | Ekspor rekap cuti (CSV/Excel/PDF) | Could |

### 3.4 Mobile (Kotlin) — belum diimplementasi
| ID | Requirement | Prioritas |
|---|---|---|
| FR-30 | Aplikasi Android mengonsumsi REST API yang sama (Retrofit) | Must |
| FR-31 | Penyimpanan offline dengan Room/SQLite | Must |
| FR-32 | Sinkronisasi otomatis saat online (flag `isSynced`) | Should |
| FR-33 | Login, lihat daftar cuti, ajukan cuti dari mobile | Must |
| FR-34 | Deteksi konektivitas (ConnectivityManager) | Should |

---

## 4. Non-Functional Requirements
| ID | Kategori | Requirement |
|---|---|---|
| NFR-01 | Performa | Response API < 500ms untuk operasi umum (single-user demo) |
| NFR-02 | Kompatibilitas | Web: Chrome/Firefox/Edge terbaru; Android: minSdk 24+ |
| NFR-03 | Usability | Form responsif, pesan error jelas berbahasa Indonesia |
| NFR-04 | Keandalan | Mobile tetap dapat baca data saat offline |
| NFR-05 | Auditability | Setiap perubahan status cuti tercatat (siapa & kapan) |
| NFR-06 | Maintainability | Kode terstruktur (controller/model/route terpisah) |

---

## 5. Model Data (3NF)

**users**: `id`, `nama_lengkap`, `npm` (unique, nullable untuk ortu/admin), `password`, `role` enum(taruna/orang_tua/admin), `nama_ibu`, `tanggal_lahir`, `tanggal_lahir_anak`, `child_id` (FK→users), `remember_token`, timestamps.

**cuti_applications**: `id`, `taruna_id` (FK→users, cascade), `alamat_cuti` (JSON), `tujuan` enum(orang_tua/kerabat), `nama_kerabat`, `nomor_kerabat`, `transportasi`, `tiket_path`, **`tanggal_mulai` (date)**, **`tanggal_selesai` (date)**, `status` enum(**pending / disetujui_ortu / disetujui / ditolak**), `approved_by_orangtua` (FK→users), `approved_at`, **`finalized_by_pengasuh` (FK→users)**, **`finalized_at`**, timestamps.

> Perubahan skema vs. baseline: +`tanggal_mulai`, +`tanggal_selesai`, +`finalized_by_pengasuh`, +`finalized_at`, dan enum `status` bertambah nilai `disetujui_ortu`. Perlu migration baru.

Relasi: `users 1—N cuti_applications` (taruna); `users(orang_tua).child_id → users(taruna).id`.

---

## 6. Kontrak API (ringkas)

| Method | Endpoint | Auth | Peran | Fungsi |
|---|---|---|---|---|
| POST | `/api/register` | – | – | Registrasi taruna |
| POST | `/api/login/taruna` | – | – | Login taruna |
| POST | `/api/login/orangtua` | – | – | Login orang tua |
| POST | `/api/login/admin` | – | – | Login admin |
| GET | `/api/user` | token | semua | Profil user aktif |
| POST | `/api/logout` | token | semua | Logout |
| GET | `/api/cuti` | token | taruna/ortu | Daftar cuti |
| POST | `/api/cuti` | token | taruna | Ajukan cuti (+file) |
| GET | `/api/cuti/{id}` | token | taruna/ortu | Detail cuti |
| PUT | `/api/cuti/{id}` | token | taruna | Ubah cuti pending |
| DELETE | `/api/cuti/{id}` | token | taruna | Hapus cuti pending |
| POST | `/api/cuti/{id}/approve` | token | ortu | Setujui tahap 1 → `disetujui_ortu` |
| POST | `/api/cuti/{id}/reject` | token | ortu | Tolak tahap 1 |
| POST | `/api/cuti/{id}/finalize` | token | admin | Finalisasi tahap 2 → `disetujui` |
| POST | `/api/cuti/{id}/admin-reject` | token | admin | Tolak tahap 2 |
| GET | `/api/admin/cuti` | token | admin | Semua cuti |
| GET | `/api/admin/statistics` | token | admin | Statistik |

Format response konsisten: `{ "status": "success|error", "data|message|errors": ... }`.

---

## 7. Rencana Pengujian (fokus UAS: Static & Dynamic Analysis)

### 7.1 Fungsional
- Blackbox testing per FR-xx.
- Uji API dengan Postman (koleksi endpoint di atas).
- Uji integrasi mobile (emulator + device fisik).

### 7.2 Static Analysis (SAST) — white-box
| Target | Tools yang disarankan |
|---|---|
| Laravel (PHP) | `larastan`/PHPStan, `enlightn/security-checker`, Psalm taint analysis, `composer audit` |
| Frontend JS | ESLint + plugin security, `npm audit` / `retire.js` |
| Kotlin/Android | Android Lint, `detekt`, MobSF (static) |
| Rahasia/secret | `gitleaks` / `trufflehog` (cek `.env`, token ter-commit) |

### 7.3 Dynamic Analysis (DAST) — black/grey-box
| Target | Tools |
|---|---|
| Web + API | OWASP ZAP (baseline & active scan), Burp Suite Community |
| API auth/otorisasi | Uji manual BOLA/IDOR, brute force login, token reuse |
| Mobile | MobSF (dynamic), inspeksi trafik via proxy (Burp), cek penyimpanan lokal |

### 7.4 Kerangka Acuan
- **Web/API:** OWASP Top 10 (2021) + OWASP ASVS.
- **Mobile:** OWASP MASVS / MASTG.
- Setiap temuan → severity (CVSS), bukti (PoC), remediasi, retest.

---

## 8. Security Requirements (target hardening)

Dirancang khusus karena produk ini akan diuji sendiri sebagai white-hat. Target: memenuhi baseline berikut sebelum submission.

| ID | Requirement |
|---|---|
| SR-01 | Otorisasi objek (BOLA/IDOR): setiap akses cuti diverifikasi kepemilikan lewat data server-side, **bukan** `taruna_id` dari klien |
| SR-02 | Orang tua hanya bisa melihat/approve cuti anak yang `child_id`-nya cocok (dari token, bukan input) |
| SR-03 | Rate limiting/throttle pada semua endpoint login (mis. 5–10/menit per IP) |
| SR-04 | Kebijakan password minimal (panjang ≥8, tidak umum) untuk akun ber-password |
| SR-05 | File tiket disimpan privat (bukan disk `public`), diakses via endpoint terautentikasi + otorisasi |
| SR-06 | Validasi upload ketat: MIME allowlist, ukuran, rename acak, cegah path traversal |
| SR-07 | Header keamanan (CSP, X-Content-Type-Options, dst.) + CORS allowlist |
| SR-08 | Password/secret tidak ter-commit; `.env` di-`.gitignore`; rotasi jika bocor |
| SR-09 | Error response tidak membocorkan stack trace/detail internal (`APP_DEBUG=false`) |
| SR-10 | Enkripsi transport (HTTPS) untuk demo produksi; mobile menolak sertifikat invalid |
| SR-11 | Admin memakai **username + password kuat** (bukan password tunggal tanpa username) |
| SR-12 | Orang tua memakai **akun ber-password** (username/identifier + password), tidak login via data yang mudah ditebak; akun tidak auto-dibuat saat login |
| SR-13 | Transisi status mengikuti state machine (`pending→disetujui_ortu→disetujui`); tolak lompat tahap & tolak ubah status oleh peran yang tidak berwenang |

---

## 9. Out of Scope (untuk versi UAS ini)
- Notifikasi real-time (WebSocket / FCM) — masuk backlog.
- Fitur chat antar-peran.
- iOS.
- Integrasi SSO kampus / SIAKAD.
- Multi-admin & manajemen role dinamis.

## 10. Backlog / Pengembangan Lanjut
Notifikasi real-time · chat · ekspor PDF/Excel · deployment production + SSL · biometric auth mobile · audit log lengkap.

---

## 11. Strategi Dua Versi & Repository

Produk dikelola dalam **satu repository** dengan dua branch yang di-push ke GitHub dan **disinkronkan** (fitur/desain baru diterapkan ke keduanya):

| Branch | Peran | Isi |
|---|---|---|
| `vuln` (rentan) | Objek uji "before" | Versi dengan kelemahan Lampiran A masih ada — untuk demo eksploitasi (PoC) saat analisis dinamis |
| `main`/`secure` (hardened) | Versi "after" | Sudah menerapkan SR-01…SR-11 — untuk membuktikan remediasi |

**Aturan sinkronisasi:** setiap perubahan **fitur atau desain** (mis. tampilan baru, field baru) diterapkan ke **kedua** branch. Yang **berbeda hanya kontrol keamanan** — supaya perbandingan before/after adil (variabel yang berubah hanya keamanan, bukan fitur).

Alur yang disarankan:
1. Kembangkan fitur di `vuln` → merge/cherry-pick ke `secure`.
2. Terapkan hardening **hanya** di `secure`.
3. Jalankan SAST/DAST di kedua branch → tabel perbandingan temuan.

### 11.1 Catatan Repository (keputusan final)
- **Repo tujuan:** `github.com/atikarr/cuti-taruna` (repo baru, akun Atika).
- Remote lama `github.com/RizqDewangga/Validasi-Cuti-Taruna` dipertahankan sebagai remote `team` untuk kolaborasi.
- `.env` sudah di-`.gitignore` (kredensial DB aman). Jalankan `gitleaks` untuk memastikan tidak ada secret di histori.
- Push pertama butuh autentikasi akun `atikarr` (repo dibuat manual di GitHub atau via `gh repo create`).

---

## Lampiran A — Temuan Keamanan Awal dari Kode Saat Ini

Ditemukan saat menelaah `AuthController.php` & `CutiController.php`. Ini justru bahan bagus untuk laporan white-hat (temukan → jelaskan → remediasi). Ringkas:

1. **Broken Authentication — Orang Tua (High).** Login hanya butuh `nama_ibu + nama_anak + tanggal_lahir_anak` — semuanya data yang bisa diketahui/ditebak, tanpa password. Sistem bahkan **auto-membuat** akun orang tua saat login. Siapa pun yang tahu 3 data ini bisa berperan sebagai orang tua.

2. **Broken Object Level Authorization / BOLA (High).** Pada `approve`/`reject`/`index` untuk orang tua, kepemilikan "anak" diperiksa dari `taruna_id` yang **dikirim klien** dan hanya dibandingkan dengan `taruna_id` milik cuti — tidak dicek terhadap `child_id` milik akun orang tua (dari token). Akibatnya orang tua mana pun bisa menyetujui/menolak/melihat cuti taruna mana pun.

3. **Admin login lemah (High).** Satu akun admin, login **password saja** tanpa username. Seeder di lampiran proposal memakai hash bcrypt default yang umum (untuk kata "password") → kredensial bisa tertebak; tanpa throttle → rentan brute force.

4. **Penyimpanan file publik (Medium).** Tiket disimpan di disk `public` (`store('tiket','public')`) → dapat diakses via URL tanpa autentikasi. Tiket berisi data perjalanan pribadi.

5. **Password policy lemah (Medium).** `min:6`.

6. **Kode `update()` cuti rusak/mati (Low).** Memvalidasi & menyimpan kolom `alamat_tujuan`/`alamat_dituju` yang tidak ada di skema; file tiket pada `store()` juga tersimpan dua kali (logika duplikat).

7. **Rate limiting belum terverifikasi (Medium).** Proposal menyebut `Limit::perMinute(60)`, perlu dipastikan benar-benar terpasang pada route login.

> Rekomendasi: jadikan tiap poin ini sebagai entri temuan pada laporan analisis, lalu tunjukkan perbaikannya (memetakan ke SR-01…SR-11).
