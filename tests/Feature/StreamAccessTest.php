<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StreamAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createVideoFile(User $user, bool $isPublic = false): File
    {
        Storage::fake('local');

        $file = File::create([
            'user_id' => $user->id,
            'name' => 'clip.mp4',
            'slug' => 'clip-mp4',
            'is_public' => $isPublic,
        ]);

        $media = $file->addMedia(UploadedFile::fake()->create('clip.mp4', 2048))
            ->usingName('clip.mp4')
            ->toMediaCollection('default', 'local');

        $media->mime_type = 'video/mp4';
        $media->save();

        return $file;
    }

    public function test_owner_can_stream_private_file_with_signed_url(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: false);

        $signed = URL::temporarySignedRoute(
            'stream.show',
            now()->addMinutes(30),
            ['id' => $file->id]
        );

        $this->actingAs($user)
            ->get($signed)
            ->assertOk()
            ->assertHeader('Content-Type', 'video/mp4')
            ->assertHeader('Accept-Ranges', 'bytes');
    }

    public function test_owner_cannot_stream_private_file_without_signature(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: false);

        $this->actingAs($user)
            ->get(route('stream.show', $file->id))
            ->assertForbidden();
    }

    public function test_other_user_cannot_stream_private_file_with_signed_url(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = $this->createVideoFile($owner, isPublic: false);

        $signed = URL::temporarySignedRoute(
            'stream.show',
            now()->addMinutes(30),
            ['id' => $file->id]
        );

        $this->actingAs($other)
            ->get($signed)
            ->assertForbidden();
    }

    public function test_expired_signed_url_is_rejected(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: false);

        $signed = URL::temporarySignedRoute(
            'stream.show',
            now()->subMinutes(1),
            ['id' => $file->id]
        );

        $this->actingAs($user)
            ->get($signed)
            ->assertForbidden();
    }

    public function test_guest_can_stream_public_file_without_signature(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: true);

        $this->get(route('stream.show', $file->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'video/mp4')
            ->assertHeader('Accept-Ranges', 'bytes');
    }

    public function test_guest_cannot_stream_private_file(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: false);

        $this->get(route('stream.show', $file->id))->assertForbidden();
    }
}
