package com.cutitaruna

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.cutitaruna.adapters.CutiAdapter
import com.cutitaruna.databinding.ActivityMainBinding
import com.cutitaruna.network.RetrofitClient
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private val adapter = CutiAdapter(emptyList())

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        binding.tvName.text = TokenStore.nama(this)

        binding.rvCuti.layoutManager = LinearLayoutManager(this)
        binding.rvCuti.adapter = adapter

        binding.swipe.setColorSchemeColors(0xFF667EEA.toInt())
        binding.swipe.setOnRefreshListener { loadCuti() }
        binding.fabAdd.setOnClickListener {
            startActivity(Intent(this, CreateCutiActivity::class.java))
        }
        binding.btnLogout.setOnClickListener { logout() }
    }

    override fun onResume() {
        super.onResume()
        loadCuti()
    }

    private fun loadCuti() {
        binding.swipe.isRefreshing = true
        lifecycleScope.launch {
            try {
                val res = RetrofitClient.api.getCuti(TokenStore.bearer(this@MainActivity))
                if (res.isSuccessful && res.body() != null) {
                    val list = res.body()!!.data
                    adapter.update(list)
                    binding.tvEmpty.visibility = if (list.isEmpty()) View.VISIBLE else View.GONE
                } else if (res.code() == 401) {
                    logout()
                } else {
                    Toast.makeText(this@MainActivity, "Gagal memuat data (${res.code()})", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MainActivity, "Tidak ada koneksi ke server: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                binding.swipe.isRefreshing = false
            }
        }
    }

    private fun logout() {
        TokenStore.clear(this)
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }
}
