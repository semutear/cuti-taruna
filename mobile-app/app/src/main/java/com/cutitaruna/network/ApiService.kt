package com.cutitaruna.network

import com.cutitaruna.models.ApproveRejectRequest
import com.cutitaruna.models.CreateCutiRequest
import com.cutitaruna.models.CreateCutiResponse
import com.cutitaruna.models.CutiListResponse
import com.cutitaruna.models.LoginAdminRequest
import com.cutitaruna.models.LoginOrangTuaRequest
import com.cutitaruna.models.LoginOrangTuaResponse
import com.cutitaruna.models.LoginRequest
import com.cutitaruna.models.LoginResponse
import com.cutitaruna.models.StatisticsResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.Path

interface ApiService {
    @POST("api/login/taruna")
    suspend fun loginTaruna(@Body body: LoginRequest): Response<LoginResponse>

    @POST("api/login/orangtua")
    suspend fun loginOrangTua(@Body body: LoginOrangTuaRequest): Response<LoginOrangTuaResponse>

    @POST("api/login/admin")
    suspend fun loginAdmin(@Body body: LoginAdminRequest): Response<LoginResponse>

    @GET("api/cuti")
    suspend fun getCuti(@Header("Authorization") token: String): Response<CutiListResponse>

    @POST("api/cuti")
    suspend fun createCuti(
        @Header("Authorization") token: String,
        @Body body: CreateCutiRequest
    ): Response<CreateCutiResponse>

    @POST("api/cuti/{id}/approve")
    suspend fun approveCuti(
        @Header("Authorization") token: String,
        @Path("id") id: Int,
        @Body body: ApproveRejectRequest
    ): Response<CreateCutiResponse>

    @POST("api/cuti/{id}/reject")
    suspend fun rejectCuti(
        @Header("Authorization") token: String,
        @Path("id") id: Int,
        @Body body: ApproveRejectRequest
    ): Response<CreateCutiResponse>

    @POST("api/cuti/{id}/finalize")
    suspend fun finalizeCuti(
        @Header("Authorization") token: String,
        @Path("id") id: Int,
        @Body body: ApproveRejectRequest
    ): Response<CreateCutiResponse>

    @GET("api/admin/cuti")
    suspend fun getCutiAdmin(@Header("Authorization") token: String): Response<CutiListResponse>

    @GET("api/admin/statistics")
    suspend fun getStatistics(@Header("Authorization") token: String): Response<StatisticsResponse>
}
