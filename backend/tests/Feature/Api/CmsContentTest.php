<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CmsContentTest extends TestCase
{
    public function test_admin_can_create_page(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $roleId = DB::table('iam.roles')
            ->where('code', 'administrator')
            ->value('id');

        if (! $roleId) {
            $roleId = (string) Str::uuid();

            DB::table('iam.roles')->insert([
                'id' => $roleId,
                'code' => 'administrator',
                'name' => 'Administrator',
                'scope' => 'platform',
            ]);
        }

        DB::table('iam.user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);

        Sanctum::actingAs($user);

        $slug = 'about-us-'.Str::lower(Str::random(8));

        $response = $this->postJson('/api/v1/cms/admin/pages', [
            'title' => 'About Us',
            'slug' => $slug,
            'status' => 'published',
            'content' => 'About YellowPages.so',
            'locale' => 'en',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $pageId = $response->json('data.id');

        if (Schema::hasTable(
            'cms.seo_metadata'
        )) {
            DB::table('cms.seo_metadata')
                ->where('content_type', 'page')
                ->where('content_id', $pageId)
                ->delete();
        }

        if (Schema::hasTable('cms.revisions')) {
            DB::table('cms.revisions')
                ->where('content_type', 'page')
                ->where('content_id', $pageId)
                ->delete();
        }

        DB::table('cms.pages')
            ->where('id', $pageId)
            ->delete();

        DB::table('iam.user_roles')
            ->where('user_id', $user->id)
            ->delete();

        $user->delete();
    }
}
