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
        Schema::create('anggota', function (Blueprint $table) {
            $table->string('id_anggota', 20)->primary();
            $table->string('nama', 100);
            $table->text('alamat')->nullable();
            $table->string('kontak', 20)->nullable();
            $table->string('status', 20)->nullable();
            $table->enum('jenis_anggota', ['nonkontrak', 'pns', 'pensiun']);
            $table->date('tanggal_daftar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
