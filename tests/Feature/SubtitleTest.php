<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Files\File;
use App\Models\Users\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubtitleTest extends TestCase
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

        $file->addMedia(UploadedFile::fake()->create('clip.mp4', 2048))
            ->usingName('clip.mp4')
            ->toMediaCollection('default', 'local');

        return $file;
    }

    public function test_owner_can_upload_subtitle(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user);

        $srt = UploadedFile::fake()->createWithContent(
            'sample.srt',
            "1\n00:00:01,000 --> 00:00:04,000\nHello world\n"
        );

        $this->actingAs($user)
            ->post(route('files.subtitles.store', $file->id), [
                'subtitle' => $srt,
                'language' => 'en',
                'label' => 'English',
                'default' => '1',
            ])
            ->assertRedirect();

        $this->assertCount(1, $file->fresh()->getMedia('subtitles'));
        $this->assertSame('en', $file->fresh()->getMedia('subtitles')->first()->getCustomProperty('language'));
        $this->assertTrue($file->fresh()->getMedia('subtitles')->first()->getCustomProperty('default'));
    }

    public function test_other_user_cannot_upload_subtitle(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = $this->createVideoFile($owner);

        $vtt = UploadedFile::fake()->createWithContent(
            'sample.vtt',
            "WEBVTT\n\n00:00:01.000 --> 00:00:04.000\nHello\n"
        );

        $this->actingAs($other)
            ->post(route('files.subtitles.store', $file->id), [
                'subtitle' => $vtt,
                'language' => 'en',
                'label' => 'English',
            ])
            ->assertForbidden();

        $this->assertCount(0, $file->fresh()->getMedia('subtitles'));
    }

    public function test_owner_can_delete_subtitle(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user);

        $media = $file->addMedia(UploadedFile::fake()->createWithContent('s.vtt', "WEBVTT\n\n00:00:01.000 --> 00:00:04.000\nHi\n"))
            ->usingName('s.vtt')
            ->toMediaCollection('subtitles', 'local');

        $this->actingAs($user)
            ->delete(route('files.subtitles.destroy', [$file->id, $media->getKey()]))
            ->assertRedirect();

        $this->assertCount(0, $file->fresh()->getMedia('subtitles'));
    }

    public function test_subtitle_is_served_as_webvtt(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: true);

        $media = $file->addMedia(UploadedFile::fake()->createWithContent(
            'sample.srt',
            "1\n00:00:01,000 --> 00:00:04,000\nHello world\n"
        ))->usingName('sample.srt')->toMediaCollection('subtitles', 'local');

        $this->get(route('subtitles.show', [$file->id, $media->getKey()]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/vtt; charset=utf-8')
            ->assertSee('WEBVTT', false)
            ->assertSee('00:00:01.000 --> 00:00:04.000', false);
    }

    public function test_guest_cannot_upload_subtitle_to_private_file(): void
    {
        $user = User::factory()->create();
        $file = $this->createVideoFile($user, isPublic: false);

        $this->post(route('files.subtitles.store', $file->id), [
            'subtitle' => UploadedFile::fake()->createWithContent('s.vtt', "WEBVTT\n"),
            'language' => 'en',
        ])->assertRedirect(route('login'));

        $this->assertCount(0, $file->fresh()->getMedia('subtitles'));
    }
}
