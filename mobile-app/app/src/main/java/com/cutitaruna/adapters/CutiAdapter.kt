package com.cutitaruna.adapters

import android.graphics.Color
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.cutitaruna.databinding.ItemCutiBinding
import com.cutitaruna.models.Cuti

class CutiAdapter(private var items: List<Cuti>) :
    RecyclerView.Adapter<CutiAdapter.VH>() {

    inner class VH(val binding: ItemCutiBinding) :
        RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VH {
        val binding = ItemCutiBinding.inflate(
            LayoutInflater.from(parent.context), parent, false
        )
        return VH(binding)
    }

    override fun onBindViewHolder(holder: VH, position: Int) {
        val c = items[position]
        val b = holder.binding
        val kota = c.alamat_cuti?.kota ?: "-"
        b.tvTitle.text = "Cuti #${c.id} → $kota"
        b.tvSubtitle.text = "Tujuan: ${c.tujuan ?: "-"} · Transportasi: ${c.transportasi ?: "-"}"
        val status = c.status ?: "pending"
        b.tvStatus.text = status
        val color = when (status) {
            "disetujui" -> Color.parseColor("#10B981")
            "disetujui_ortu" -> Color.parseColor("#3B82F6")
            "ditolak" -> Color.parseColor("#EF4444")
            else -> Color.parseColor("#F59E0B")
        }
        b.tvStatus.setBackgroundColor(color)
    }

    override fun getItemCount() = items.size

    fun update(newItems: List<Cuti>) {
        items = newItems
        notifyDataSetChanged()
    }
}
