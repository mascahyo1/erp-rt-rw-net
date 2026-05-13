<?php

use Illuminate\Database\Migrations\Migration;
use App\Support\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saas_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key');
            $table->enum('type', ['text', 'file']);
            $table->text('value');
            $table->text('descripton')->nullable();
            $table->timestamps();
            $table->blameable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_configs');
    }
};
