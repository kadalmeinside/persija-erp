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
        Schema::create('tbl_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_karyawan_creator');
            $table->unsignedBigInteger('id_karyawan_assignee');
            $table->unsignedBigInteger('id_departemen')->nullable();
            $table->unsignedBigInteger('id_program_kerja')->nullable();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['To Do', 'In Progress', 'Review', 'Done'])->default('To Do');
            $table->enum('priority', ['Low', 'Medium', 'High'])->default('Medium');
            $table->date('due_date')->nullable();
            $table->integer('order_index')->default(0); // For Kanban drag-and-drop order
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_karyawan_creator')->references('id')->on('tbl_karyawan')->onDelete('cascade');
            $table->foreign('id_karyawan_assignee')->references('id')->on('tbl_karyawan')->onDelete('cascade');
            $table->foreign('id_departemen')->references('id')->on('tbl_departemen')->onDelete('set null');
            $table->foreign('id_program_kerja')->references('id')->on('tbl_program_kerja')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_tasks');
    }
};
