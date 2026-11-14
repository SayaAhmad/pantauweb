<?php

namespace Tests\Feature;

use App\Enums\CheckStatus;
use App\Models\CheckResult;
use App\Models\Monitor;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_filters_by_server_and_exports_csv(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->for($user)->create(['name' => 'Server Uji']);
        $otherServer = Server::factory()->for($user)->create(['name' => 'Server Lain']);

        $monitor = Monitor::factory()->for($user)->create([
            'name' => 'Website Laporan',
            'server_id' => $server->id,
        ]);

        $otherMonitor = Monitor::factory()->for($user)->create([
            'name' => 'Website Diabaikan',
            'server_id' => $otherServer->id,
        ]);

        CheckResult::factory()->for($monitor)->create([
            'status' => CheckStatus::Up,
            'response_time_ms' => 150,
            'checked_at' => now()->subHours(2),
        ]);

        CheckResult::factory()->for($otherMonitor)->create([
            'checked_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->get('/reports?server='.$server->id)
            ->assertOk()
            ->assertSee('Website Laporan')
            ->assertDontSee('Website Diabaikan');

        $response = $this->actingAs($user)
            ->get('/reports/export?server='.$server->id)
            ->assertOk();

        $this->assertStringContainsString('Website Laporan', $response->streamedContent());
        $this->assertStringNotContainsString('Website Diabaikan', $response->streamedContent());
    }

    public function test_incident_page_renders_with_filters(): void
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->for($user)->create(['name' => 'Monitor Insiden']);

        \App\Models\Incident::factory()->for($monitor)->open()->create([
            'started_at' => now()->subMinutes(15),
        ]);

        $this->actingAs($user)
            ->get('/incidents?monitor='.$monitor->id.'&state=open')
            ->assertOk()
            ->assertSee('Monitor Insiden')
            ->assertSee('Berlangsung');
    }
}
