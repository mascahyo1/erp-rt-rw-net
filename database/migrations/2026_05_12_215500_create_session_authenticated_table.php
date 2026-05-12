<?php

use App\Support\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_authenticated', function (Blueprint $table) {
            $table->string('session_id');
            $table->string('guard_name');
            $table->string('user_type');
            $table->uuid('user_id');
            $table->longText('payload')->nullable();
            $table->integer('last_activity');

            $table->primary(['session_id', 'guard_name']);
            $table->index(['user_type', 'user_id']);

            $table->foreign('session_id')
                ->references('id')
                ->on('sessions')
                ->onDelete('cascade');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['user_type', 'user_id']);
            $table->dropColumn(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_type')->nullable();
            $table->uuid('user_id')->nullable();
            $table->index(['user_type', 'user_id']);
        });

        Schema::dropIfExists('session_authenticated');
    }
};
