<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CutiApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CutiController extends Controller
{
    /**
     * Menampilkan daftar cuti milik taruna yang login (untuk role taruna)
     * Atau untuk orang tua, menampilkan cuti anaknya (perlu parameter anak_id)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role == 'taruna') {
            // Taruna melihat cuti miliknya sendiri
            $cuti = CutiApplication::where('taruna_id', $user->id)
                        ->with('taruna', 'approver', 'finalizedBy')
                        ->latest()
                        ->get();
        } elseif ($user->role == 'orang_tua') {
            // Orang tua: butuh taruna_id dari request (misal dikirim via query ?taruna_id=...)
            // Karena kita tidak tahu anaknya siapa, kita asumsikan dikirim dari frontend
            $request->validate([
                'taruna_id' => 'required|exists:users,id'
            ]);
            $cuti = CutiApplication::where('taruna_id', $request->taruna_id)
                        ->with('taruna', 'approver', 'finalizedBy')
                        ->latest()
                        ->get();
        } else {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $cuti
        ]);
    }

    /**
     * Menyimpan pengajuan cuti baru (hanya untuk taruna)
     */
    public function store(Request $request)
{
    $user = $request->user();
    if ($user->role != 'taruna') {
        return response()->json(['status' => 'error', 'message' => 'Hanya taruna yang bisa mengajukan cuti'], 403);
    }

    $validator = Validator::make($request->all(), [
        // Periode cuti
        'tanggal_mulai' => 'required|date|after_or_equal:today',
        'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        // Alamat cuti (komponen)
        'alamat_cuti.jalan' => 'required|string',
        'alamat_cuti.rt_rw' => 'required|string',
        'alamat_cuti.kelurahan' => 'required|string',
        'alamat_cuti.kecamatan' => 'required|string',
        'alamat_cuti.kota' => 'required|string',
        'alamat_cuti.provinsi' => 'required|string',
        'tujuan' => 'required|in:orang_tua,kerabat',
        'nama_kerabat' => 'required_if:tujuan,kerabat|string|nullable',
        'nomor_kerabat' => 'required_if:tujuan,kerabat|string|nullable',
        'transportasi' => 'required|string|in:kereta,pesawat,bus,travel,ojol,pribadi',
        'tiket' => 'required_if:transportasi,kereta,pesawat,bus,travel|file|mimes:pdf,jpeg,jpg,png|max:2048|nullable', // 2MB
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $data = [
        'taruna_id' => $user->id,
        'tanggal_mulai' => $request->tanggal_mulai,
        'tanggal_selesai' => $request->tanggal_selesai,
        'alamat_cuti' => $request->alamat_cuti,
        'tujuan' => $request->tujuan,
        'transportasi' => $request->transportasi,
        'status' => 'pending',
    ];

    if ($request->tujuan === 'kerabat') {
        $data['nama_kerabat'] = $request->nama_kerabat;
        $data['nomor_kerabat'] = $request->nomor_kerabat;
    }

    if ($request->hasFile('tiket') && $request->file('tiket')->isValid()) {
            $path = $request->file('tiket')->store('tiket', 'public');
            $data['tiket_path'] = $path;
        }

    // Handle file upload jika transportasi memerlukan tiket
    $transportasiWajibTiket = ['kereta', 'pesawat', 'bus', 'travel'];
    if (in_array($request->transportasi, $transportasiWajibTiket)) {
        if (!$request->hasFile('tiket')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Untuk transportasi ini wajib mengunggah tiket.'
            ], 422);
        }
        $path = $request->file('tiket')->store('tiket', 'public');
        $data['tiket_path'] = $path;
    }

    $cuti = CutiApplication::create($data);

    return response()->json([
        'status' => 'success',
        'data' => $cuti
    ], 201);
}

    /**
     * Menampilkan detail satu cuti
     */
    public function show(Request $request, $id)
    {
        $cuti = CutiApplication::with('taruna', 'approver', 'finalizedBy')->findOrFail($id);

        // Cek otorisasi: hanya pemilik (taruna) atau orang tua yang berhak
        $user = $request->user();
        if ($user->role == 'taruna' && $cuti->taruna_id != $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak berhak melihat cuti ini'], 403);
        }
        // Untuk orang tua, kita perlu cek apakah cuti ini milik anaknya. Sederhananya, kita cek dari taruna_id yang dikirim di query? Atau kita bisa cek relasi orang tua-taruna. Untuk sementara, lewati.

        return response()->json([
            'status' => 'success',
            'data' => $cuti
        ]);
    }

    /**
     * Mengupdate cuti (hanya jika status masih pending dan user adalah pemilik)
     */
    public function update(Request $request, $id)
    {
        $cuti = CutiApplication::findOrFail($id);
        $user = $request->user();

        if ($user->role != 'taruna' || $cuti->taruna_id != $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($cuti->status != 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Cuti sudah diproses, tidak bisa diubah'], 400);
        }

        $validator = Validator::make($request->all(), [
            'alamat_tujuan' => 'sometimes|string',
            'alamat_dituju' => 'sometimes|string',
            'transportasi' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $cuti->update($request->only('alamat_tujuan', 'alamat_dituju', 'transportasi'));

        return response()->json([
            'status' => 'success',
            'data' => $cuti
        ]);
    }

    /**
     * Menghapus cuti (hanya jika pending)
     */
    public function destroy(Request $request, $id)
    {
        $cuti = CutiApplication::findOrFail($id);
        $user = $request->user();

        if ($user->role != 'taruna' || $cuti->taruna_id != $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($cuti->status != 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Cuti sudah diproses, tidak bisa dihapus'], 400);
        }

        $cuti->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cuti berhasil dihapus'
        ]);
    }

    /**
     * Tahap 1: Orang tua menyetujui cuti (pending -> disetujui_ortu)
     */
    public function approve(Request $request, $id)
    {
        $cuti = CutiApplication::findOrFail($id);
        $user = $request->user();

        if ($user->role != 'orang_tua') {
            return response()->json(['status' => 'error', 'message' => 'Hanya orang tua yang dapat menyetujui'], 403);
        }

        // Validasi apakah cuti ini milik anak orang tua tersebut
        // Kita perlu taruna_id yang dikirim dari client
        $request->validate([
            'taruna_id' => 'required|exists:users,id'
        ]);

        if ($cuti->taruna_id != $request->taruna_id) {
            return response()->json(['status' => 'error', 'message' => 'Cuti bukan milik anak Anda'], 403);
        }

        // Cek apakah status masih pending (tahap 1 belum diproses)
        if ($cuti->status != 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Cuti sudah diproses sebelumnya'], 400);
        }

        $cuti->status = 'disetujui_ortu';
        $cuti->approved_by_orangtua = $user->id;
        $cuti->approved_at = now();
        $cuti->save();

        return response()->json([
            'status' => 'success',
            'data' => $cuti
        ]);
    }

    /**
     * Tahap 2: Pengasuh/admin memfinalisasi cuti (disetujui_ortu -> disetujui)
     */
    public function finalize(Request $request, $id)
    {
        $cuti = CutiApplication::findOrFail($id);
        $user = $request->user();

        if ($user->role != 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Hanya pengasuh/admin yang dapat memfinalisasi'], 403);
        }

        if ($cuti->status != 'disetujui_ortu') {
            return response()->json(['status' => 'error', 'message' => 'Cuti belum disetujui orang tua, tidak bisa difinalisasi'], 400);
        }

        $cuti->status = 'disetujui';
        $cuti->finalized_by_pengasuh = $user->id;
        $cuti->finalized_at = now();
        $cuti->save();

        return response()->json([
            'status' => 'success',
            'data' => $cuti
        ]);
    }

    /**
     * Menolak cuti. Orang tua bisa menolak di tahap 1 (pending),
     * pengasuh/admin bisa menolak di tahap 2 (disetujui_ortu).
     */
    public function reject(Request $request, $id)
    {
        $cuti = CutiApplication::findOrFail($id);
        $user = $request->user();

        if ($user->role == 'orang_tua') {
            $request->validate([
                'taruna_id' => 'required|exists:users,id'
            ]);

            if ($cuti->taruna_id != $request->taruna_id) {
                return response()->json(['status' => 'error', 'message' => 'Cuti bukan milik anak Anda'], 403);
            }

            if ($cuti->status != 'pending') {
                return response()->json(['status' => 'error', 'message' => 'Cuti sudah diproses sebelumnya'], 400);
            }

            $cuti->approved_by_orangtua = $user->id;
            $cuti->approved_at = now();
        } elseif ($user->role == 'admin') {
            if ($cuti->status != 'disetujui_ortu') {
                return response()->json(['status' => 'error', 'message' => 'Cuti belum disetujui orang tua'], 400);
            }

            $cuti->finalized_by_pengasuh = $user->id;
            $cuti->finalized_at = now();
        } else {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $cuti->status = 'ditolak';
        $cuti->save();

        return response()->json([
            'status' => 'success',
            'data' => $cuti
        ]);
    }
}
