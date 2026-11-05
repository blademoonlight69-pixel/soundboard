<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Hotkey;
use App\Models\ModerationLog;
use App\Models\Sound;
use App\Models\SoundReport;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_and_setting_relation(): void
    {
        $user = User::factory()->create();
        $setting = UserSetting::create([
            'user_id'       => $user->id,
            'master_volume' => 75,
            'theme'         => 'dark',
        ]);

        $this->assertEquals(75, $user->settings->master_volume);
    }

    public function test_sound_belongs_to_user_and_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Gaming SFX',
            'slug' => 'gaming-sfx',
        ]);

        $sound = Sound::create([
            'user_id'            => $user->id,
            'category_id'        => $category->id,
            'title'              => 'Victory Trumpet',
            'file_path'          => 'sounds/1/test.mp3',
            'file_name_original' => 'test.mp3',
            'file_size'          => 2048000,
            'duration'           => 125,
            'mime_type'          => 'audio/mpeg',
            'status'             => 'private',
        ]);

        $this->assertEquals('Victory Trumpet', $user->sounds()->first()->title);
        $this->assertEquals('Gaming SFX', $sound->category->name);
        $this->assertEquals('2 MB', $sound->file_size_human);
        $this->assertEquals('2:05', $sound->duration_formatted);
    }

    public function test_favorite_relationship(): void
    {
        $user = User::factory()->create();
        $sound = Sound::create([
            'user_id'            => $user->id,
            'title'              => 'Taunt',
            'file_path'          => 'sounds/1/taunt.mp3',
            'file_name_original' => 'taunt.mp3',
            'file_size'          => 500,
            'status'             => 'public',
        ]);

        Favorite::create([
            'user_id'  => $user->id,
            'sound_id' => $sound->id,
        ]);

        $this->assertEquals(1, $user->favorites()->count());
        $this->assertEquals(1, $sound->favoritedBy()->count());
    }

    public function test_cascade_delete_on_user_removes_sounds_and_hotkeys(): void
    {
        $user = User::factory()->create();
        $sound = Sound::create([
            'user_id'            => $user->id,
            'title'              => 'Temporary Sound',
            'file_path'          => 'sounds/temp.mp3',
            'file_name_original' => 'temp.mp3',
            'status'             => 'private',
        ]);

        Hotkey::create([
            'user_id'   => $user->id,
            'sound_id'  => $sound->id,
            'key_combo' => 'Ctrl+1',
        ]);

        $this->assertDatabaseHas('sounds', ['id' => $sound->id]);
        $this->assertDatabaseHas('hotkeys', ['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('sounds', ['id' => $sound->id]);
        $this->assertDatabaseMissing('hotkeys', ['user_id' => $user->id]);
    }

    public function test_unique_hotkey_per_user_constraint(): void
    {
        $user = User::factory()->create();
        $sound1 = Sound::create([
            'user_id'            => $user->id,
            'title'              => 'S1',
            'file_path'          => 's1.mp3',
            'file_name_original' => 's1.mp3',
            'status'             => 'public',
        ]);
        $sound2 = Sound::create([
            'user_id'            => $user->id,
            'title'              => 'S2',
            'file_path'          => 's2.mp3',
            'file_name_original' => 's2.mp3',
            'status'             => 'public',
        ]);

        Hotkey::create([
            'user_id'   => $user->id,
            'sound_id'  => $sound1->id,
            'key_combo' => 'F1',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Hotkey::create([
            'user_id'   => $user->id,
            'sound_id'  => $sound2->id,
            'key_combo' => 'F1', // Duplicate hotkey for same user
        ]);
    }
}
