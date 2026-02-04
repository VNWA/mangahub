<?php

namespace Tests\Feature\Admin;

use App\Jobs\DeleteImageStorageJob;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MangaAvatarResizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_manga_store_resizes_and_converts_avatar_using_vnwa_config(): void
    {
        Storage::fake('public');

        config()->set('vnwa.manga.avatar.width', 300);
        config()->set('vnwa.manga.avatar.height', 400);
        config()->set('vnwa.manga.avatar.format', 'webp');
        config()->set('vnwa.manga.avatar.quality', 90);

        $user = User::factory()->create();
        Role::findOrCreate('admin');
        $user->assignRole('admin');
        $this->actingAs($user);

        $response = $this->post(route('mangas.store'), [
            'name' => 'Test Manga',
            'status' => 'ongoing',
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 1200, 1200),
        ]);

        $response->assertRedirect(route('mangas.index'));

        $manga = Manga::query()->firstOrFail();

        $this->assertNotEmpty($manga->avatar);
        $this->assertStringEndsWith('.webp', $manga->avatar);
        Storage::disk('public')->assertExists($manga->avatar);

        $fullPath = Storage::disk('public')->path($manga->avatar);
        [$width, $height] = getimagesize($fullPath);

        $this->assertSame(300, $width);
        $this->assertSame(400, $height);
    }

    public function test_manga_update_resizes_new_avatar_and_dispatches_delete_job_for_old_avatar(): void
    {
        Bus::fake();
        Storage::fake('public');

        config()->set('vnwa.manga.avatar.width', 300);
        config()->set('vnwa.manga.avatar.height', 400);
        config()->set('vnwa.manga.avatar.format', 'webp');

        $user = User::factory()->create();
        Role::findOrCreate('admin');
        $user->assignRole('admin');
        $this->actingAs($user);

        // Seed an existing avatar on disk
        $oldPath = 'mangas/avatars/old.webp';
        Storage::disk('public')->put($oldPath, 'old');

        /** @var Manga $manga */
        $manga = Manga::factory()->create([
            'name' => 'Test Manga',
            'status' => 'ongoing',
            'avatar' => $oldPath,
        ]);

        $response = $this->put(route('mangas.update', $manga), [
            'name' => $manga->name,
            'status' => $manga->status,
            'avatar' => UploadedFile::fake()->image('new.png', 800, 800),
        ]);

        $response->assertRedirect(route('mangas.index'));

        $manga->refresh();
        $this->assertNotSame($oldPath, $manga->avatar);
        Storage::disk('public')->assertExists($manga->avatar);

        Bus::assertDispatched(DeleteImageStorageJob::class, function (DeleteImageStorageJob $job) use ($oldPath) {
            return $job->urls === [$oldPath] && $job->directories === [];
        });
    }
}
