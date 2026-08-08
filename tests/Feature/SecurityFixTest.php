<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Resources\Files\FileResource;
use App\Models\Files\File;
use App\Models\Users\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createVideoFile(User $user, bool $isPublic = false, ?string $password = null): File
    {
        Storage::fake('local');

        $file = File::create([
            'user_id' => $user->id,
            'name' => 'clip.mp4',
            'slug' => 'clip-mp4',
            'is_public' => $isPublic,
            'password' => $password ? Hash::make($password) : null,
        ]);

        $media = $file->addMedia(UploadedFile::fake()->create('clip.mp4', 2048))
            ->usingName('clip.mp4')
            ->toMediaCollection('default', 'local');

        $media->mime_type = 'video/mp4';
        $media->save();

        return $file;
    }

    public function test_guest_cannot_stream_public_password_protected_file(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: true, password: 'secret-pass');

        $this->get(route('stream.show', $file->id))
            ->assertForbidden();
    }

    public function test_guest_can_stream_public_password_protected_file_after_unlock(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: true, password: 'secret-pass');

        $this->withSession(['file_unlocked_'.$file->id => true])
            ->get(route('stream.show', $file->id))
            ->assertOk();
    }

    public function test_owner_can_stream_public_password_protected_file(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: true, password: 'secret-pass');

        $this->actingAs($user)
            ->get(route('stream.show', $file->id))
            ->assertOk();
    }

    public function test_guest_cannot_fetch_subtitles_of_public_password_protected_file(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: true, password: 'secret-pass');

        $subtitle = $file->addMedia(UploadedFile::fake()->create('sub.srt', 100))
            ->usingName('sub.srt')
            ->toMediaCollection('subtitles', 'local');

        $this->get(route('subtitles.show', [$file->id, $subtitle->id]))
            ->assertForbidden();
    }

    public function test_webhook_url_rejects_private_host(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('webhooks.store'), [
                'url' => 'http://127.0.0.1:8080/hook',
                'events' => ['file.uploaded'],
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_webhook_url_rejects_localhost(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('webhooks.store'), [
                'url' => 'http://localhost/hook',
                'events' => ['file.uploaded'],
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_file_password_requires_min_eight_characters(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'user_id' => $user->id,
            'name' => 'doc.pdf',
            'slug' => 'doc',
        ]);

        $this->actingAs($user)
            ->patch(route('files.update', $file->id), ['password' => 'short'])
            ->assertSessionHasErrors('password');
    }

    public function test_share_password_requires_min_eight_characters(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $file = File::create([
            'user_id' => $user->id,
            'name' => 'doc.pdf',
            'slug' => 'doc',
        ]);

        $this->actingAs($user)
            ->post(route('files.share', $file->id), [
                'shared_with' => $target->email,
                'permission' => 'view',
                'password' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_unlock_route_is_throttled(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: true, password: 'secret-pass');

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('public.unlock', $file->id), ['password' => 'wrong-pass']);
        }

        $this->post(route('public.unlock', $file->id), ['password' => 'wrong-pass'])
            ->assertStatus(429);
    }

    public function test_signed_stream_url_in_resource_for_private_file(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: false);

        $resource = (new FileResource($file->load('media')))
            ->response()
            ->getData(true);

        $streamUrl = $resource['data']['stream_url'];
        $this->assertStringContainsString('signature=', $streamUrl);
    }

    public function test_stream_response_sanitizes_filename_header(): void
    {
        $user = User::factory()->create();

        Storage::fake('local');

        $file = File::create([
            'user_id' => $user->id,
            'name' => 'clip',
            'slug' => 'clip-malicious',
            'is_public' => true,
        ]);

        $media = $file->addMedia(UploadedFile::fake()->create('clip.mp4', 2048))
            ->usingName('bad"name"\r\nX-Injected.mp4')
            ->toMediaCollection('default', 'local');

        $media->mime_type = 'video/mp4';
        $media->save();

        $response = $this->get(route('stream.show', $file->id));
        $response->assertOk();

        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringNotContainsString('X-Injected', $disposition);
        $this->assertStringNotContainsString("\r", $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
    }
}
