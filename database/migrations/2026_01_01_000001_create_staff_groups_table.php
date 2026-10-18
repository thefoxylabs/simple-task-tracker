<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6366f1');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staff')->after('password');
            $table->foreignId('staff_group_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('job_title')->nullable()->after('staff_group_id');
            $table->string('phone', 50)->nullable()->after('job_title');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_group_id');
            $table->dropColumn(['role', 'job_title', 'phone', 'is_active']);
        });

        Schema::dropIfExists('staff_groups');
    }
};
