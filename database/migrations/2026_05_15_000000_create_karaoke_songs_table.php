<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karaoke_songs', function (Blueprint $table): void {
            $table->id();
            $table->string('video_id')->unique();
            $table->string('title');
            $table->string('artist_name')->nullable()->index();
            $table->string('channel_title')->nullable();
            $table->string('thumbnail_url', 2048)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('source_query')->nullable()->index();
            $table->json('features')->nullable();
            $table->json('genres')->nullable();
            $table->string('language', 32)->nullable()->index();
            $table->unsignedInteger('search_count')->default(1);
            $table->unsignedInteger('played_count')->default(0);
            $table->timestamp('cached_at')->nullable()->index();
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karaoke_songs');
    }
};
