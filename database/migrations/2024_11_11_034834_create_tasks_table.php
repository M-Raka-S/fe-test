<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Priorities;
use App\Statuses;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->boolean('done')->default(false);
            $table->string('title');
            $table->string('description');
            $table->dateTime('due_date');
            $table->enum('status', array_column(Statuses::cases(), 'value'));
            $table->enum('priority', array_column(Priorities::cases(), 'value'));
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
