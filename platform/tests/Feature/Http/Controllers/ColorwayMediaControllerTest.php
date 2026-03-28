<?php

use App\Models\Account;
use App\Models\Colorway;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

// storeMedia

test('user can upload images for their colorway', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('colorways.media.store', $colorway), [
        'images' => [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png'),
        ],
    ]);

    $response->assertRedirect(route('colorways.edit', $colorway));
    expect($colorway->media()->count())->toBe(2);
    expect($colorway->media()->where('is_primary', true)->count())->toBe(1);
});

test('first uploaded image is marked as primary when no media exists', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    $this->actingAs($user)->post(route('colorways.media.store', $colorway), [
        'images' => [
            UploadedFile::fake()->image('first.jpg'),
            UploadedFile::fake()->image('second.jpg'),
        ],
    ]);

    $media = $colorway->media()->orderBy('id')->get();
    expect($media[0]->is_primary)->toBeTrue();
    expect($media[1]->is_primary)->toBeFalse();
});

test('new uploads are not marked primary when media already exists', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $colorway->media()->create([
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'colorways/1/existing.jpg',
        'disk' => 'public',
        'file_name' => 'existing.jpg',
        'is_primary' => true,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)->post(route('colorways.media.store', $colorway), [
        'images' => [UploadedFile::fake()->image('new.jpg')],
    ]);

    expect($colorway->media()->where('is_primary', false)->where('file_name', 'new.jpg')->exists())->toBeTrue();
});

test('store media requires authentication', function () {
    $colorway = Colorway::factory()->create(['account_id' => Account::factory()->create()->id]);

    $response = $this->post(route('colorways.media.store', $colorway), [
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ]);

    $response->assertRedirect(route('login'));
});

test('user cannot upload images to another accounts colorway', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $otherColorway = Colorway::factory()->create(['account_id' => Account::factory()->create()->id]);

    $response = $this->actingAs($user)->post(route('colorways.media.store', $otherColorway), [
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ]);

    $response->assertForbidden();
});

test('store media requires at least one image', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('colorways.media.store', $colorway), [
        'images' => [],
    ]);

    $response->assertSessionHasErrors('images');
});

test('store media only accepts image files', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('colorways.media.store', $colorway), [
        'images' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
    ]);

    $response->assertSessionHasErrors('images.0');
});

// destroyMedia

test('user can delete media from their colorway', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $media = $colorway->media()->create([
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'colorways/1/photo.jpg',
        'disk' => 'public',
        'file_name' => 'photo.jpg',
        'is_primary' => true,
        'created_by' => $user->id,
    ]);
    Storage::disk('public')->put('colorways/1/photo.jpg', 'fake content');

    $response = $this->actingAs($user)->delete(route('colorways.media.destroy', [$colorway, $media]));

    $response->assertRedirect(route('colorways.edit', $colorway));
    $this->assertSoftDeleted('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing('colorways/1/photo.jpg');
});

test('user cannot delete media from another accounts colorway', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $otherColorway = Colorway::factory()->create(['account_id' => Account::factory()->create()->id]);
    $media = $otherColorway->media()->create([
        'mediable_type' => Colorway::class,
        'mediable_id' => $otherColorway->id,
        'file_path' => 'colorways/99/photo.jpg',
        'disk' => 'public',
        'file_name' => 'photo.jpg',
        'is_primary' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('colorways.media.destroy', [$otherColorway, $media]));

    $response->assertForbidden();
    $this->assertNotSoftDeleted('media', ['id' => $media->id]);
});

test('destroy media requires authentication', function () {
    $colorway = Colorway::factory()->create(['account_id' => Account::factory()->create()->id]);
    $media = $colorway->media()->create([
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'colorways/1/photo.jpg',
        'disk' => 'public',
        'file_name' => 'photo.jpg',
        'is_primary' => true,
    ]);

    $response = $this->delete(route('colorways.media.destroy', [$colorway, $media]));

    $response->assertRedirect(route('login'));
});
