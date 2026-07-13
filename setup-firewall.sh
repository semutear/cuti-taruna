#!/bin/bash

# ==============================================================================
# SCRIPT KONFIGURASI FIREWALL (iptables)
# Mengintegrasikan Aturan Praktikum Keamanan Jaringan & Pengamanan Web Cuti Taruna
# ==============================================================================

echo "======================================================================"
echo "       KONFIGURASI FIREWALL STATEFUL (iptables)"
echo "======================================================================"
echo ""

# Pastikan dijalankan sebagai root/sudo
if [ "$EUID" -ne 0 ]; then
  echo "Error: Silakan jalankan script ini menggunakan sudo!"
  exit 1
fi

# Pilih Mode Konfigurasi
echo "Pilih Mode Konfigurasi:"
echo "1) Mode Gateway Praktikum (Forwarding VM Metasploitable - IP Lab)"
echo "2) Mode Pengamanan Server Web Cuti Taruna (Local Host Input)"
read -p "Masukkan pilihan (1/2): " PILIHAN

if [ "$PILIHAN" -eq 1 ]; then
  # ----------------------------------------------------------------------------
  # MODE 1: STATEFUL FIREWALL GATEWAY (Sesuai Praktikum Pertemuan 12)
  # ----------------------------------------------------------------------------
  echo ""
  echo "[+] Menerapkan Aturan Stateful Firewall Gateway..."
  
  # IP berdasarkan topologi aktual lab:
  KALI_IP="192.168.56.20"
  UBUNTU_GW="192.168.56.9"
  METASPLOITABLE_IP="192.168.56.6"

  # 1. Bersihkan aturan rantai FORWARD
  iptables -F FORWARD
  echo "[-] Rantai FORWARD dibersihkan."

  # 2. Aturan 1: Stateful Golden Rule (Izinkan paket ESTABLISHED dan RELATED)
  iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
  echo "[+] Aturan 1: ESTABLISHED,RELATED diizinkan."

  # 3. Aturan 2: Akses Web Terbatas (Hanya port 80 menuju protected server)
  iptables -A FORWARD -p tcp -d $METASPLOITABLE_IP --dport 80 -m conntrack --ctstate NEW -j ACCEPT
  echo "[+] Aturan 2: Akses HTTP (Port 80) ke $METASPLOITABLE_IP diizinkan."

  # 4. Aturan 3: Akses Keluar Server (Izinkan koneksi baru keluar dari internal server)
  iptables -A FORWARD -s $METASPLOITABLE_IP -m conntrack --ctstate NEW -j ACCEPT
  echo "[+] Aturan 3: Koneksi keluar baru (NEW) dari $METASPLOITABLE_IP diizinkan."

  # 5. Aturan 4: Kebijakan Default DROP (Blokir akses tidak sah seperti SSH/FTP dari luar)
  iptables -P FORWARD DROP
  echo "[+] Aturan 4: Default Policy FORWARD diatur ke DROP."

  # Aktifkan IP Forwarding secara runtime
  sysctl -w net.ipv4.ip_forward=1 > /dev/null
  echo "[+] IP Forwarding diaktifkan."

elif [ "$PILIHAN" -eq 2 ]; then
  # ----------------------------------------------------------------------------
  # MODE 2: PENGAMANAN SERVER WEB CUTI TARUNA (Local Host)
  # ----------------------------------------------------------------------------
  echo ""
  echo "[+] Menerapkan Aturan Pengamanan Server Web Cuti Taruna..."

  # 1. Bersihkan aturan lama
  iptables -F INPUT
  echo "[-] Rantai INPUT dibersihkan."

  # 2. Izinkan koneksi loopback (localhost wajib aktif untuk internal service)
  iptables -A INPUT -i lo -j ACCEPT
  echo "[+] Loopback (lo) diizinkan."

  # 3. Izinkan koneksi yang sudah terjalin (ESTABLISHED, RELATED)
  iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
  echo "[+] Lalu lintas ESTABLISHED,RELATED diizinkan."

  # 4. Izinkan SSH (Port 22) hanya dari IP Administrator (Misal: Kali Linux 192.168.56.20)
  ADMIN_IP="192.168.56.20"
  iptables -A INPUT -p tcp -s $ADMIN_IP --dport 22 -m conntrack --ctstate NEW -j ACCEPT
  echo "[+] Akses SSH (Port 22) dibatasi hanya untuk IP Admin ($ADMIN_IP)."

  # 5. Izinkan rute akses ke API / Web Cuti Taruna (Port 8000) dari jaringan lokal
  iptables -A INPUT -p tcp --dport 8000 -m conntrack --ctstate NEW -j ACCEPT
  echo "[+] Akses Web/API Cuti Taruna (Port 8000) dibuka untuk publik."

  # 6. Atur kebijakan default INPUT menjadi DROP (Blokir semua port lain)
  iptables -P INPUT DROP
  echo "[+] Default Policy INPUT diatur ke DROP."

else
  echo "Pilihan tidak valid!"
  exit 1
fi

echo ""
echo "=== ATURAN IPTABLES AKTIF SAAT INI ==="
iptables -L -n -v
echo ""
echo "Konfigurasi berhasil diterapkan!"
