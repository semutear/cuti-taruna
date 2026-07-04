package com.cutitaruna

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

class CreateCutiActivity : AppCompatActivity() {

    private lateinit var binding: ActivityCreateCutiBinding

    // Transportasi tanpa tiket agar tidak perlu unggah file
    private val transportasiOptions = listOf("pribadi", "ojol")

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityCreateCutiBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Ajukan Cuti"
        supportActionBar?.setDisplayHomeAsUpEnabled(true)

        binding.spTransportasi.adapter = ArrayAdapter(
            this, android.R.layout.simple_spinner_dropdown_item, transportasiOptions
        )

        binding.btnSubmit.setOnClickListener { submit() }
    }

    override fun onSupportNavigateUp(): Boolean {
        finish(); return true
    }

    private fun submit() {
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
            transportasi = transportasi
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
