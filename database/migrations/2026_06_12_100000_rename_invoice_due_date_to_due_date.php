<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Rename `invoice_due_date` → `due_date` di cust_internet_invcs.
     *
     * Alasan:
     *   - Drop migration sebelumnya (2026_06_08_130000) menghapus `due_date` sebagai
     *     "redundan dengan invoice_due_date".
     *   - Tapi model CustInternetInvc, TagihanController, Tagihan.vue, dan banyak
     *     places lain masih reference `due_date`. Hasilnya: create/edit invoice
     *     selalu 500 SQLSTATE "Column not found: due_date".
     *   - Cleanest fix: standardize ke satu nama. `due_date` adalah nama yang
     *     dipakai di seluruh codebase (model fillable, casts, controller, Vue).
     *
     *   - InvoiceGeneratorService dan DemoSeeder yang masih pakai `invoice_due_date`
     *     di-update terpisah untuk pakai `due_date`.
     */
    public function up(): void
    {
        if (Schema::hasColumn('cust_internet_invcs', 'invoice_due_date') && !Schema::hasColumn('cust_internet_invcs', 'due_date')) {
            DB::statement('ALTER TABLE `cust_internet_invcs` CHANGE `invoice_due_date` `due_date` DATE NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cust_internet_invcs', 'due_date') && !Schema::hasColumn('cust_internet_invcs', 'invoice_due_date')) {
            DB::statement('ALTER TABLE `cust_internet_invcs` CHANGE `due_date` `invoice_due_date` DATE NULL');
        }
    }
};
