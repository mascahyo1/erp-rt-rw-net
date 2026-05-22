<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DaftarPaketSimpleTest extends DuskTestCase
{
    private static array $cleanupUserIds = [];
    private static array $cleanupRoleIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        static::enableVideoRecording(true, 'tests/Browser/videos');
    }

    private function createUserWithPerms(array $permNames): AdminCompany
    {
        $role = Role::create([
            'id' => (string) Str::uuid(),
            'scope' => 'admin_perusahaan',
            'name' => 'Test Role ' . Str::random(6),
            'is_active' => true,
            'display_order' => 1,
        ]);
        self::$cleanupRoleIds[] = $role->id;

        $permIds = Permission::whereIn('name', $permNames)->pluck('id');
        foreach ($permIds as $pId) {
            \DB::table('role_permissions')->insert([
                'id' => (string) Str::uuid(),
                'role_id' => $role->id,
                'permission_id' => $pId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $user = AdminCompany::factory()->create([
            'name' => 'Test User ' . Str::random(6),
            'email' => 'test.' . Str::random(6) . '@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $user->id;

        \DB::table('model_has_roles')->insert([
            'id' => (string) Str::uuid(),
            'role_id' => $role->id,
            'model_id' => $user->id,
            'model_type' => AdminCompany::class,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    public function test_page_load_with_video_recording(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10);

            $this->captureVideoFrame('page-loaded');

            $browser->pause(500);
            $this->captureVideoFrame('after-pause-1');

            $browser->pause(500);
            $this->captureVideoFrame('after-pause-2');

            $browser->pause(500);
            $this->captureVideoFrame('after-pause-3');

            $browser->assertSee('Paket Customer');
        });
    }

    public static function tearDownAfterClass(): void
    {
        \DB::table('model_has_roles')->whereIn('model_id', self::$cleanupUserIds)->delete();
        AdminCompany::whereIn('id', self::$cleanupUserIds)->forceDelete();
        \DB::table('role_permissions')->whereIn('role_id', self::$cleanupRoleIds)->delete();
        Role::whereIn('id', self::$cleanupRoleIds)->delete();
        parent::tearDownAfterClass();
    }
}