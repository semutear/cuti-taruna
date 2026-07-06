package com.cutitaruna

import android.app.DatePickerDialog
import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.cutitaruna.databinding.ActivityCreateCutiBinding
import com.cutitaruna.models.AlamatCuti
import com.cutitaruna.models.CreateCutiRequest
import com.cutitaruna.network.RetrofitClient
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

class CreateCutiActivity : AppCompatActivity() {

    private lateinit var binding: ActivityCreateCutiBinding
    private val dateFormat = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())

    // Transportasi tanpa tiket agar tidak perlu unggah file
    private val transportasiOptions = listOf("pribadi", "ojol")

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityCreateCutiBinding.inflate(layoutInflater)
        setContentView(binding.root)

        binding.btnBack.setOnClickListener { finish() }

        binding.spTransportasi.adapter = ArrayAdapter(
            this, android.R.layout.simple_spinner_dropdown_item, transportasiOptions
        )

        binding.etTanggalMulai.setOnClickListener { pickDate(binding.etTanggalMulai) }
        binding.etTanggalSelesai.setOnClickListener { pickDate(binding.etTanggalSelesai) }

        binding.btnSubmit.setOnClickListener { submit() }
    }

    private fun pickDate(target: android.widget.EditText) {
        val cal = Calendar.getInstance()
        DatePickerDialog(this, { _, year, month, day ->
            cal.set(year, month, day)
            target.setText(dateFormat.format(cal.time))
        }, cal.get(Calendar.YEAR), cal.get(Calendar.MONTH), cal.get(Calendar.DAY_OF_MONTH)).show()
    }

    private fun submit() {
        val tanggalMulai = binding.etTanggalMulai.text.toString().trim()
        val tanggalSelesai = binding.etTanggalSelesai.text.toString().trim()

        if (tanggalMulai.isEmpty() || tanggalSelesai.isEmpty()) {
            Toast.makeText(this, "Tanggal mulai dan selesai wajib diisi", Toast.LENGTH_SHORT).show()
            return
        }
        try {
            val mulai = dateFormat.parse(tanggalMulai)
            val selesai = dateFormat.parse(tanggalSelesai)
            if (mulai != null && selesai != null && selesai.before(mulai)) {
                Toast.makeText(this, "Tanggal selesai harus setelah tanggal mulai", Toast.LENGTH_SHORT).show()
                return
            }
        } catch (e: Exception) {
            Toast.makeText(this, "Format tanggal tidak valid", Toast.LENGTH_SHORT).show()
            return
        }

        val alamat = AlamatCuti(
            jalan = binding.etJalan.text.toString().trim(),
            rt_rw = binding.etRtRw.text.toString().trim(),
            kelurahan = binding.etKelurahan.text.toString().trim(),
            kecamatan = binding.etKecamatan.text.toString().trim(),
            kota = binding.etKota.text.toString().trim(),
            provinsi = binding.etProvinsi.text.toString().trim()
        )
        if (listOf(alamat.jalan, alamat.rt_rw, alamat.kelurahan, alamat.kecamatan, alamat.kota, alamat.provinsi).any { it.isEmpty() }) {
            Toast.makeText(this, "Semua field alamat wajib diisi", Toast.LENGTH_SHORT).show()
            return
        }
        val transportasi = binding.spTransportasi.selectedItem.toString()
        val req = CreateCutiRequest(
            alamat_cuti = alamat,
            tujuan = "orang_tua",
            transportasi = transportasi,
            tanggal_mulai = tanggalMulai,
            tanggal_selesai = tanggalSelesai
        )
        binding.btnSubmit.isEnabled = false
        lifecycleScope.launch {
            try {
                val res = RetrofitClient.api.createCuti(TokenStore.bearer(this@CreateCutiActivity), req)
                if (res.isSuccessful) {
                    Toast.makeText(this@CreateCutiActivity, "Pengajuan cuti berhasil", Toast.LENGTH_SHORT).show()
                    finish()
                } else {
                    Toast.makeText(this@CreateCutiActivity, "Gagal (${res.code()}): periksa data", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@CreateCutiActivity, "Gagal terhubung: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                binding.btnSubmit.isEnabled = true
            }
        }
    }
}
