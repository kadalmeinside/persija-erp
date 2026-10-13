<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('approval_notifications')->default(true);
            $table->boolean('leave_notifications')->default(true);
            $table->boolean('attendance_notifications')->default(true);
            $table->boolean('task_notifications')->default(true);
            $table->boolean('announcement_notifications')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('tbl_user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_token', 500);
            $table->string('device_type', 30)->default('android'); // android, ios, web
            $table->string('device_name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_user_device_tokens');
        Schema::dropIfExists('tbl_notification_preferences');
    }
};
