<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'auto_hadir')) {
                $table->boolean('auto_hadir')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'auto_hadir')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('auto_hadir'));
        }
    }
};