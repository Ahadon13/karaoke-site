<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karaoke_curated_songs', function (Blueprint $table): void {
            $table->id();
            $table->string('video_id')->unique();
            $table->string('title');
            $table->string('thumbnail_url')->nullable();
            $table->string('channel_title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karaoke_curated_songs');
    }
};
