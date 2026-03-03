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
        Schema::create('antrians', function (Blueprint $table) {
           $table->id();

            // Foreign key to layanans
            $table->foreignId('layanan_id')
                ->constrained('layanans')
                ->cascadeOnDelete();

            // Date for daily reset logic
            $table->date('tanggal');

            // Raw sequence number (1,2,3,...)
            $table->unsignedInteger('nomor_urutan');

            // Formatted number (e.g. EN-001)
            $table->string('nomor_antrian');

            // Status workflow
            $table->enum('status', [
                'menunggu',
                'dipanggil',
                'selesai',
                'dilewati'
            ])->default('menunggu');

            // Tracking times
            $table->timestamp('dipanggil_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();

            $table->timestamps();

            // Prevent duplicate numbers per layanan per day
            $table->unique(['layanan_id', 'tanggal', 'nomor_urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antrians');
    }
};
