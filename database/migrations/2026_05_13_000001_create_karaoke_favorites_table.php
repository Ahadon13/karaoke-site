<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karaoke_favorites', function (Blueprint $table): void {
            $table->id();
            $table->string('video_id')->unique();
            $table->string('title');
            $table->string('thumbnail_url')->nullable();
            $table->string('channel_title')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamp('favorited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karaoke_favorites');
    }
};
