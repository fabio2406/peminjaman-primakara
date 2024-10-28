<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pinjams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // Relasi ke tabel users
            $table->date('loan_date'); // Tanggal pinjam
            $table->date('return_date'); // Tanggal rencana pengembalian
            $table->date('actual_return_date')->nullable(); // Tanggal pengembalian aktual (bisa null jika belum dikembalikan)
            $table->enum('status', ['pending', 'approved', 'rejected', 'returned'])->default('pending'); // Status peminjaman
            $table->text('keterangan_peminjam')->nullable(); // Keterangan dari peminjam
            $table->text('keterangan_penyetuju')->nullable(); // Keterangan dari penyetuju (admin)
            $table->enum('status_warek', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinjams');
    }
};
