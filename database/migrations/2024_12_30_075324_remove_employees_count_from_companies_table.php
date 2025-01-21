<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('employees_count');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->integer('employees_count')->default(0); // Tambahkan kembali kolom jika rollback
        });
    }
};
