<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karaoke_queue_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('karaoke_room_id')->constrained()->cascadeOnDelete();
            $table->string('singer_name', 80)->default('Guest');
            $table->string('video_id')->index();
            $table->string('title');
            $table->string('thumbnail_url')->nullable();
            $table->string('channel_title')->nullable();
            $table->string('searched_keyword')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->string('status', 16)->default('queued')->index();
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['karaoke_room_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karaoke_queue_items');
    }
};
