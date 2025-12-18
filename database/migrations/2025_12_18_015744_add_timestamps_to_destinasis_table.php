<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('destinasis', function (Blueprint $table) {
            // Tambahkan timestamps jika belum ada
            if (!Schema::hasColumn('destinasis', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('img');
            }
            if (!Schema::hasColumn('destinasis', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down()
    {
        Schema::table('destinasis', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};