<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('type'); // Ej: cedula, ingresos, contrato
            $table->string('file_path'); // ubicación en storage/app/public
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('client_documents');
    }
};
