<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiUserIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_monitors_on_dashboard(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $monitorA = Monitor::factory()->for($userA)->create(['name' => 'Website User A']);
        $monitorB = Monitor::factory()->for($userB)->create(['name' => 'Website User B']);

        $this->actingAs($userA)
            ->get('/')
            ->assertOk()
            ->assertSee('Website User A')
            ->assertDontSee('Website User B');

        $this->actingAs($userB)
            ->get('/')
            ->assertOk()
            ->assertSee('Website User B')
            ->assertDontSee('Website User A');
    }

    public function test_user_cannot_access_or_edit_other_user_monitor(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $monitorB = Monitor::factory()->for($userB)->create(['name' => 'Website User B']);

        $this->actingAs($userA)
            ->get(route('monitors.show', $monitorB))
            ->assertNotFound();

        $this->actingAs($userA)
            ->get(route('monitors.edit', $monitorB))
            ->assertNotFound();

        $this->actingAs($userA)
            ->put(route('monitors.update', $monitorB), [
                'name' => 'Dibajak',
                'type' => 'http',
                'url' => 'https://example.com',
                'interval_seconds' => 300,
                'timeout_seconds' => 10,
                'confirmation_threshold' => 2,
                'recovery_threshold' => 2,
            ])
            ->assertNotFound();

        $this->assertSame('Website User B', $monitorB->fresh()->name);
    }

    public function test_user_cannot_access_or_edit_other_user_server(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $serverB = Server::factory()->for($userB)->create(['name' => 'Server B']);

        $this->actingAs($userA)
            ->get(route('servers.edit', $serverB))
            ->assertNotFound();

        $this->actingAs($userA)
            ->delete(route('servers.destroy', $serverB))
            ->assertNotFound();

        $this->assertDatabaseHas('servers', ['id' => $serverB->id]);
    }

    public function test_admin_has_user_monitoring_dashboard_and_no_website_monitoring_sections(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['name' => 'Client Satria']);
        Monitor::factory()->for($user)->create(['name' => 'Client Monitor']);

        $response = $this->actingAs($admin)->get('/');

        $response->assertOk()
            ->assertSee('Pemantauan Pengguna')
            ->assertSee('Client Satria')
            ->assertDontSee('Waktu respons')
            ->assertDontSee('Masa berlaku SSL');
    }

    public function test_admin_is_forbidden_from_website_monitoring_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/monitors')->assertForbidden();
        $this->actingAs($admin)->get('/servers')->assertForbidden();
        $this->actingAs($admin)->get('/incidents')->assertForbidden();
        $this->actingAs($admin)->get('/reports')->assertForbidden();
    }
}
