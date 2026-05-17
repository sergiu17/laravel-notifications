<?php

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->ulid('batch_id');

            $table->string('channel');
            $table->string('fallback_channel')->nullable();
            $table->string('recipient');
            $table->text('content');

            $table->timestamp('scheduled_at')->nullable();

            $table->enum('priority', array_column(NotificationPriority::cases(), 'value'))->default(NotificationPriority::Default->value);
            $table->enum('status', array_column(NotificationStatus::cases(), 'value'))->default(NotificationStatus::Pending->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['batch_id']);
            $table->index(['scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
