package com.cutitaruna.network

import com.cutitaruna.models.CreateCutiRequest
import com.cutitaruna.models.CreateCutiResponse
import com.cutitaruna.models.CutiListResponse
import com.cutitaruna.models.LoginRequest
import com.cutitaruna.models.LoginResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST

interface ApiService {
    @POST("api/login/taruna")
    suspend fun loginTaruna(@Body body: LoginRequest): Response<LoginResponse>

    @GET("api/cuti")
    suspend fun getCuti(@Header("Authorization") token: String): Response<CutiListResponse>

    @POST("api/cuti")
    suspend fun createCuti(
        @Header("Authorization") token: String,
        @Body body: CreateCutiRequest
    ): Response<CreateCutiResponse>
}
