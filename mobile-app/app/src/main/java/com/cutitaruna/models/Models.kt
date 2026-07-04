package com.cutitaruna.models

// ---- Auth ----
data class LoginRequest(val npm: String, val password: String)
data class User(val id: Int, val nama_lengkap: String?, val role: String?)
data class LoginData(val token: String, val user: User)
data class LoginResponse(val status: String, val data: LoginData)

// ---- Cuti ----
data class AlamatCuti(
    val jalan: String,
    val rt_rw: String,
    val kelurahan: String,
    val kecamatan: String,
    val kota: String,
    val provinsi: String
)

data class Cuti(
    val id: Int,
    val alamat_cuti: AlamatCuti?,
    val tujuan: String?,
    val transportasi: String?,
    val status: String?,
    val tanggal_mulai: String?,
    val tanggal_selesai: String?
)

data class CutiListResponse(val status: String, val data: List<Cuti>)

data class CreateCutiRequest(
    val alamat_cuti: AlamatCuti,
    val tujuan: String,
    val transportasi: String,
    val nama_kerabat: String? = null,
    val nomor_kerabat: String? = null
)

data class CreateCutiResponse(val status: String, val data: Cuti)
