<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karaoke_play_histories', function (Blueprint $table): void {
            $table->id();
            $table->string('video_id')->index();
            $table->string('title');
            $table->string('thumbnail_url')->nullable();
            $table->string('channel_title')->nullable();
            $table->string('searched_keyword')->nullable();
            $table->unsignedInteger('played_count')->default(1);
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karaoke_play_histories');
    }
};
