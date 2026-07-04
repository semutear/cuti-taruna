// Top-level build file
plugins {
    // AGP 9+ sudah punya dukungan Kotlin bawaan; plugin kotlin.android tidak diperlukan.
    alias(libs.plugins.android.application) apply false
}
