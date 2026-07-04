package com.cutitaruna

import android.content.Context

object TokenStore {
    private const val PREF = "cuti_prefs"
    private const val KEY_TOKEN = "token"
    private const val KEY_NAMA = "nama"

    private fun prefs(ctx: Context) =
        ctx.getSharedPreferences(PREF, Context.MODE_PRIVATE)

    fun save(ctx: Context, token: String, nama: String?) {
        prefs(ctx).edit().putString(KEY_TOKEN, token).putString(KEY_NAMA, nama).apply()
    }

    fun token(ctx: Context): String? = prefs(ctx).getString(KEY_TOKEN, null)
    fun nama(ctx: Context): String? = prefs(ctx).getString(KEY_NAMA, null)
    fun bearer(ctx: Context): String = "Bearer " + (token(ctx) ?: "")

    fun clear(ctx: Context) {
        prefs(ctx).edit().clear().apply()
    }
}
