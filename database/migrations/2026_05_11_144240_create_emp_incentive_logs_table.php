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
        Schema::create('emp_incentive_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('emp_incentive_id')->constrained('emp_incentives')->onDelete('restrict');
            $table->foreignUuid('cust_internet_invcs_id')->constrained('cust_internet_invcs')->onDelete('restrict');
            $table->decimal('amount', 20, 2);
            $table->date('date');
            $table->string('proof_file')->nullable();
            $table->approvable();
            $table->timestamps();
            $table->blameable();
            $table->softDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_incentive_logs');
    }
};
