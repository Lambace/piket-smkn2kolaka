<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'jenis_kelamin')) {
                $table->string('jenis_kelamin', 1)->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'nip')) {
                $table->string('nip', 20)->nullable()->after('jenis_kelamin');
            }
            if (!Schema::hasColumn('users', 'golongan')) {
                $table->string('golongan', 5)->nullable()->after('nip');
            }
            if (!Schema::hasColumn('users', 'status_kepegawaian')) {
                $table->string('status_kepegawaian', 10)->nullable()->after('golongan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['jenis_kelamin', 'nip', 'golongan', 'status_kepegawaian']);
        });
    }
};