<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Hotkey;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SoundboardFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sounds_catalog_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sounds/catalog');
        $response->assertStatus(200);
        $response->assertSee('Katalog Suara Publik');
    }

    public function test_catalog_only_shows_public_sounds_not_private_or_hidden(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $publicSound = Sound::create([
            'user_id'            => $otherUser->id,
            'title'              => 'Public Meme Sound',
            'file_path'          => 'sounds/public.mp3',
            'file_name_original' => 'public.mp3',
            'status'             => 'public',
        ]);

        $privateSound = Sound::create([
            'user_id'            => $otherUser->id,
            'title'              => 'Secret Private Audio',
            'file_path'          => 'sounds/secret.mp3',
            'file_name_original' => 'secret.mp3',
            'status'             => 'private',
        ]);

        $hiddenSound = Sound::create([
            'user_id'            => $otherUser->id,
            'title'              => 'Hidden Violating Audio',
            'file_path'          => 'sounds/hidden.mp3',
            'file_name_original' => 'hidden.mp3',
            'status'             => 'hidden',
        ]);

        $response = $this->actingAs($user)->get('/sounds/catalog');
        $response->assertStatus(200);
        $response->assertSee('Public Meme Sound');
        $response->assertDontSee('Secret Private Audio');
        $response->assertDontSee('Hidden Violating Audio');
    }

    public function test_my_sounds_page_renders_users_own_sounds_including_private(): void
    {
        $user = User::factory()->create();



$sound = Sound::create([
            'user_id'            => $user->id,
            'title'              => 'My Personal Private Sound',
            'file_path'          => 'sounds/my.mp3',
            'file_name_original' => 'my.mp3',
            'status'             => 'private',
        ]);

        $response = $this->actingAs($user)->get('/my-sounds');
        $response->assertStatus(200);
        $response->assertSee('My Personal Private Sound');
        $response->assertSee('Privat');
    }

    public function test_user_can_upload_sound_successfully(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('victory.mp3', 500, 'audio/mpeg');

        $response = $this->actingAs($user)->post('/sounds', [
            'title'       => 'Victory Fanfare',
            'status'      => 'public',
            'description' => 'Played when winning a match',
            'audio'       => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sounds', [
            'user_id' => $user->id,
            'title'   => 'Victory Fanfare',
            'status'  => 'public',
        ]);
    }

    public function test_favorites_page_renders_and_toggle_favorite_works(): void
    {
        $user = User::factory()->create();
        $sound = Sound::create([
            'user_id'            => $user->id,
            'title'              => 'Favorite SFX',
            'file_path'          => 'sounds/fav.mp3',
            'file_name_original' => 'fav.mp3',
            'status'             => 'public',
        ]);

        // Toggle add to favorite
        $response = $this->actingAs($user)->post("/sounds/{$sound->id}/favorite");
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('favorites', [
            'user_id'  => $user->id,
            'sound_id' => $sound->id,
        ]);

        // Visit favorites page
        $response = $this->actingAs($user)->get('/favorites');
        $response->assertStatus(200);
        $response->assertSee('Favorite SFX');

        // Toggle remove from favorite
        $response = $this->actingAs($user)->post("/sounds/{$sound->id}/favorite");
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('favorites', [
            'user_id'  => $user->id,
            'sound_id' => $sound->id,
        ]);
    }

    public function test_hotkeys_page_renders_and_user_can_manage_hotkeys(): void
    {
        $user = User::factory()->create();
        $sound = Sound::create([
            'user_id'            => $user->id,
            'title'              => 'Hotkey Audio',
            'file_path'          => 'sounds/hk.mp3',
            'file_name_original' => 'hk.mp3',
            'status'             => 'public',
        ]);

        $response = $this->actingAs($user)->get('/hotkeys');
        $response->assertStatus(200);
        $response->assertSee('Hotkey Manager');

        // Store Hotkey
        $response = $this->actingAs($user)->post('/hotkeys', [
            'sound_id'  => $sound->id,
            'key_combo' => 'F1',
            'label'     => 'Quick Victory',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('hotkeys', [
            'user_id'   => $user->id,
            'sound_id'  => $sound->id,
            'key_combo' => 'F1',
        ]);

        $hotkey = Hotkey::where('user_id', $user->id)->first();

        // Delete Hotkey
        $response = $this->actingAs($user)->delete("/hotkeys/{$hotkey->id}");
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('hotkeys', ['id' => $hotkey->id]);
    }

    public function test_user_cannot_delete_another_users_hotkey(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $sound = Sound::create([
            'user_id'            => $user1->id,
            'title'              => 'Sound 1',
            'file_path'          => 's1.mp3',
            'file_name_original' => 's1.mp3',
            'status'             => 'public',
        ]);

        $hotkey = Hotkey::create([
            'user_id'   => $user1->id,
            'sound_id'  => $sound->id,
            'key_combo' => 'F2',
        ]);

        // User 2 attempts to delete User 1's hotkey
        $response = $this->actingAs($user2)->delete("/hotkeys/{$hotkey->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('hotkeys', ['id' => $hotkey->id]);
    }

    public function test_user_cannot_play_or_stream_private_sound_belonging_to_another_user(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $privateSound = Sound::create([
            'user_id'            => $owner->id,
            'title'              => 'Private Diary Audio',
            'file_path'          => 'sounds/private.mp3',
            'file_name_original' => 'private.mp3',
            'status'             => 'private',
        ]);

        // Stranger tries to play owner's private sound
        $response = $this->actingAs($stranger)->get("/sounds/{$privateSound->id}/play");
        $response->assertStatus(403);
    }

    public function test_stop_all_hotkey_defaults_to_end_for_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/hotkeys')
            ->assertStatus(200)
            ->assertSee('End')
            ->assertSee('Hotkey Stop All Bawaan Sistem');
    }

    public function test_user_can_update_and_reset_stop_all_hotkey(): void
    {
        $user = User::factory()->create();

        // Update Stop All to F12
        $response = $this->actingAs($user)->post('/hotkeys/stop-all', [
            'stop_all_hotkey' => 'F12',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('F12', $user->fresh()->settings->stop_all_hotkey);

        // Reset back to default 'End'
        $response = $this->actingAs($user)->post('/hotkeys/stop-all/reset');
        $response->assertSessionHas('success');
        $this->assertEquals('End', $user->fresh()->settings->stop_all_hotkey);
    }

       public function test_prevent_conflict_between_stop_all_and_sound_hotkey(): void
    {
        $user = User::factory()->create();

        $user->settings()->create([
            'master_volume' => 80,
            'theme' => 'dark',
            'stop_all_hotkey' => 'End',
        ]);

        $sound = Sound::create([
            'user_id'            => $user->id,
            'title'              => 'Test SFX',
            'file_path'          => 's.mp3',
            'file_name_original' => 's.mp3',
            'status'             => 'public',
        ]);

        // Tombol End tidak boleh digunakan untuk hotkey suara.
        $response = $this->actingAs($user)->post('/hotkeys', [
            'sound_id'  => $sound->id,
            'key_combo' => 'End',
        ]);

        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('hotkeys', [
            'user_id'   => $user->id,
            'key_combo' => 'End',
        ]);

        // Tombol Q boleh digunakan untuk hotkey suara.
        $this->actingAs($user)->post('/hotkeys', [
            'sound_id'  => $sound->id,
            'key_combo' => 'Q',
        ]);

        $this->assertDatabaseHas('hotkeys', [
            'user_id'   => $user->id,
            'key_combo' => 'Q',
        ]);

        // Tombol Q tidak boleh digunakan untuk Stop All.
        $response = $this->actingAs($user)->post('/hotkeys/stop-all', [
            'stop_all_hotkey' => 'Q',
        ]);

        $response->assertSessionHas('error');

        $this->assertEquals(
            'End',
            $user->fresh()->settings->stop_all_hotkey
        );
    }
}