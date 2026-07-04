package com.cutitaruna

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.cutitaruna.databinding.ActivityLoginBinding
import com.cutitaruna.models.LoginRequest
import com.cutitaruna.network.RetrofitClient
import kotlinx.coroutines.launch

class LoginActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLoginBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Jika sudah punya token, langsung ke Main
        if (TokenStore.token(this) != null) {
            goMain()
            return
        }

        binding.btnLogin.setOnClickListener { doLogin() }
    }

    private fun doLogin() {
        val npm = binding.etNpm.text.toString().trim()
        val pass = binding.etPassword.text.toString()
        if (npm.isEmpty() || pass.isEmpty()) {
            Toast.makeText(this, "NPM dan password wajib diisi", Toast.LENGTH_SHORT).show()
            return
        }
        setLoading(true)
        lifecycleScope.launch {
            try {
                val res = RetrofitClient.api.loginTaruna(LoginRequest(npm, pass))
                if (res.isSuccessful && res.body() != null) {
                    val data = res.body()!!.data
                    TokenStore.save(this@LoginActivity, data.token, data.user.nama_lengkap)
                    Toast.makeText(this@LoginActivity, "Selamat datang, ${data.user.nama_lengkap}", Toast.LENGTH_SHORT).show()
                    goMain()
                } else {
                    Toast.makeText(this@LoginActivity, "Login gagal: NPM/password salah", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@LoginActivity, "Gagal terhubung ke server: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                setLoading(false)
            }
        }
    }

    private fun setLoading(loading: Boolean) {
        binding.progress.visibility = if (loading) android.view.View.VISIBLE else android.view.View.GONE
        binding.btnLogin.isEnabled = !loading
    }

    private fun goMain() {
        startActivity(Intent(this, MainActivity::class.java))
        finish()
    }
}
