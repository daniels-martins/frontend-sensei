<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_results', function (Blueprint $table) {
            $table->id();
            $table->string('audit_type');
            $table->integer('score')->default(0);
            $table->integer('issues_count')->default(0);
            $table->json('issues');
            $table->text('context')->nullable();
            $table->timestamps();

            $table->index('audit_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_results');
    }
};
