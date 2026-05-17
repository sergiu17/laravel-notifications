<?php

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_single_notification(): void
    {
        $response = $this->postJson('/api/notifications', [
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Your code is 123456',
            'priority' => 'high',
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure(['id', 'batch_id', 'channel', 'recipient', 'content', 'status']);

        $this->assertDatabaseHas('notifications', [
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Your code is 123456',
            'status' => NotificationStatus::Pending->value,
        ]);
    }

    public function test_rejects_missing_required_fields(): void
    {
        $this->postJson('/api/notifications')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['recipient', 'channel', 'content']);
    }

    public function test_rejects_invalid_channel(): void
    {
        $this->postJson('/api/notifications', [
            'recipient' => 'alice@example.com',
            'channel' => 'pigeon',
            'content' => 'hi',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);
    }

    public function test_creates_batch_with_shared_batch_id(): void
    {
        $payload = [
            'notifications' => [
                ['recipient' => 'a@example.com', 'channel' => 'email', 'content' => 'one'],
                ['recipient' => 'b@example.com', 'channel' => 'email', 'content' => 'two'],
                ['recipient' => 'c@example.com', 'channel' => 'email', 'content' => 'three'],
            ],
        ];

        $response = $this->postJson('/api/notifications/batch', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure(['batch_id']);

        $batchId = $response->json('batch_id');

        $this->assertSame(3, Notification::where('batch_id', $batchId)->count());
    }

    public function test_rejects_batch_over_1000_items(): void
    {
        $payload = [
            'notifications' => array_fill(0, 1001, [
                'recipient' => 'test@example.com',
                'channel' => 'email',
                'content' => 'x',
            ]),
        ];

        $this->postJson('/api/notifications/batch', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['notifications']);
    }

    public function test_rejects_empty_batch(): void
    {
        $this->postJson('/api/notifications/batch', ['notifications' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['notifications']);
    }

    public function test_lists_notifications_with_pagination(): void
    {
        Notification::factory()->count(30)->create();

        $response = $this->getJson('/api/notifications');

        $response->assertSuccessful();
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_filters_by_batch_id(): void
    {
        $targetBatch = '01jzbatcha000000000000000';
        Notification::factory()->count(2)->create(['batch_id' => $targetBatch]);
        Notification::factory()->count(3)->create(['batch_id' => '01jzbatchb000000000000000']);

        $response = $this->getJson("/api/notifications?batch_id={$targetBatch}");

        $this->assertCount(2, $response->json());
    }

    public function test_filters_by_status(): void
    {
        Notification::factory()->count(2)->pending()->create();
        Notification::factory()->count(3)->processed()->create();

        $response = $this->getJson('/api/notifications?status=processed');

        $this->assertCount(3, $response->json('data'));
        foreach ($response->json('data') as $row) {
            $this->assertSame('processed', $row['status']);
        }
    }

    public function test_cancels_a_pending_notification(): void
    {
        $notification = Notification::factory()->pending()->create();

        $this->deleteJson("/api/notifications/{$notification->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);
    }

    public function test_rejects_cancellation_of_processed_notification(): void
    {
        $notification = Notification::factory()->processed()->create();

        $this->deleteJson("/api/notifications/{$notification->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'deleted_at' => null,
        ]);
    }
}
