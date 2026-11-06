<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WasteManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['category' => 'plastic', 'confidence' => 0.91, 'label' => 'Plastic Waste', 'needs_review' => false])]);
    }

    private function payload(): array
    {
        return ['title' => 'Sampah di taman', 'description' => 'Banyak botol plastik di dekat bangku taman.',
            'category' => 'plastic', 'address' => 'Taman Menteng', 'latitude' => -6.2, 'longitude' => 106.8,
            'photo' => UploadedFile::fake()->image('waste.jpg')];
    }

    public function test_registration_cannot_assign_admin_role(): void
    {
        $response = $this->postJson('/api/v1/register', ['name' => 'Nadia', 'email' => 'nadia@test.id', 'password' => 'Secret123!', 'password_confirmation' => 'Secret123!', 'role' => 'admin']);
        $response->assertCreated()->assertJsonPath('user.role', 'user')->assertJsonStructure(['token']);
        $this->assertDatabaseHas('users', ['email' => 'nadia@test.id', 'role' => 'user']);
    }

    public function test_real_bearer_token_login_logout_and_invalid_password(): void
    {
        $user = User::factory()->create(['password' => 'Secret123!']);
        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        $token = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'Secret123!'])->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guest_is_rejected_and_user_cannot_access_admin_routes(): void
    {
        $this->getJson('/api/v1/reports')->assertUnauthorized();
        $this->get('/dashboard')->assertRedirect('/login');
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_user_only_sees_own_reports_and_cannot_access_another_photo(): void
    {
        $user = User::factory()->create();
        $own = WasteReport::factory()->create(['user_id' => $user->id]);
        $other = WasteReport::factory()->create();
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/reports')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->getJson('/api/v1/reports/'.$other->id)->assertForbidden();
        $this->getJson('/api/v1/reports/'.$other->id.'/photo')->assertForbidden();
        $this->putJson('/api/v1/reports/'.$other->id, $this->payload())->assertForbidden();
        $this->deleteJson('/api/v1/reports/'.$other->id)->assertForbidden();
    }

    public function test_create_report_saves_real_photo_prediction_and_initial_history(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/reports', array_merge($this->payload(), ['status' => 'completed', 'user_id' => 999, 'ai_confidence' => 1]));
        $response->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.ai_confidence', 0.91);
        $report = WasteReport::firstOrFail();
        $this->assertSame($user->id, $report->user_id);
        Storage::disk('local')->assertExists($report->photo_path);
        $this->assertDatabaseHas('status_histories', ['waste_report_id' => $report->id, 'status' => 'pending']);
        $this->get('/api/v1/reports/'.$report->id.'/photo')->assertOk();
    }

    public function test_invalid_gps_and_non_image_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/reports', array_merge($this->payload(), ['latitude' => 100, 'photo' => UploadedFile::fake()->create('bad.txt', 10, 'text/plain')]))
            ->assertUnprocessable()->assertJsonValidationErrors(['latitude', 'photo']);
    }

    public function test_pending_report_can_be_edited_and_deleted_but_processed_report_cannot(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $report = WasteReport::factory()->create(['user_id' => $user->id]);
        Storage::disk('local')->put($report->photo_path, 'old');
        $this->putJson('/api/v1/reports/'.$report->id, $this->payload())->assertOk()->assertJsonPath('data.title', 'Sampah di taman');
        Storage::disk('local')->assertMissing('reports/test.jpg');
        $report->refresh();
        $path = $report->photo_path;
        $this->deleteJson('/api/v1/reports/'.$report->id)->assertOk();
        Storage::disk('local')->assertMissing($path);
        $processed = WasteReport::factory()->create(['user_id' => $user->id, 'status' => 'in_progress']);
        $this->putJson('/api/v1/reports/'.$processed->id, $this->payload())->assertForbidden();
        $this->deleteJson('/api/v1/reports/'.$processed->id)->assertForbidden();
        $this->patchJson('/api/v1/reports/'.$processed->id.'/status', ['status' => 'completed'])->assertForbidden();
    }

    public function test_admin_status_requires_rejection_reason_and_records_actor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $report = WasteReport::factory()->create();
        $this->patchJson('/api/v1/reports/'.$report->id.'/status', ['status' => 'rejected'])->assertUnprocessable();
        $this->patchJson('/api/v1/reports/'.$report->id.'/status', ['status' => 'rejected', 'note' => 'Duplikat laporan sebelumnya.'])->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertDatabaseHas('status_histories', ['user_id' => $admin->id, 'status' => 'rejected', 'note' => 'Duplikat laporan sebelumnya.']);
        $this->patchJson('/api/v1/reports/'.$report->id.'/status', ['status' => 'rejected', 'note' => 'Duplikat'])->assertOk();
        $this->assertDatabaseCount('status_histories', 1);
    }

    public function test_search_category_and_status_filters_and_statistics_are_scoped(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        WasteReport::factory()->create(['user_id' => $user->id, 'title' => 'Botol di taman', 'status' => 'completed']);
        WasteReport::factory()->create(['user_id' => $user->id, 'category' => 'organic', 'status' => 'pending']);
        WasteReport::factory()->count(3)->create(['status' => 'completed']);
        $this->getJson('/api/v1/reports?q=Botol&category=plastic&status=completed')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/dashboard')->assertJsonPath('data.stats.total', 2)->assertJsonPath('data.stats.completed', 1)->assertJsonPath('data.stats.completion_rate', 50);
        $this->getJson('/api/v1/reports?status=invalid')->assertUnprocessable();
    }

    public function test_ai_outage_preserves_manual_reporting(): void
    {
        Http::swap(new Factory);
        Http::fake(['*' => Http::response([], 503)]);
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/predictions', ['photo' => UploadedFile::fake()->image('waste.jpg')])->assertStatus(503);
        $this->postJson('/api/v1/reports', $this->payload())->assertCreated()->assertJsonPath('data.ai_confidence', null);
    }

    public function test_admin_manages_users_with_safe_deletion_and_self_protection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $response = $this->postJson('/api/v1/users', ['name' => 'Operator', 'email' => 'op@test.id', 'role' => 'user', 'password' => 'Secret123', 'password_confirmation' => 'Secret123'])->assertCreated();
        $id = $response->json('data.id');
        $this->putJson('/api/v1/users/'.$id, ['name' => 'Operator Baru', 'email' => 'op@test.id', 'role' => 'admin'])->assertOk();
        $this->deleteJson('/api/v1/users/'.$id)->assertOk();
        $this->deleteJson('/api/v1/users/'.$admin->id)->assertUnprocessable();
        $this->putJson('/api/v1/users/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'role' => 'user'])->assertUnprocessable();
        $owner = User::factory()->create();
        WasteReport::factory()->create(['user_id' => $owner->id]);
        $this->deleteJson('/api/v1/users/'.$owner->id)->assertUnprocessable();
    }

    public function test_web_pages_render_for_both_roles(): void
    {
        $user = User::factory()->create();
        $report = WasteReport::factory()->create(['user_id' => $user->id]);
        foreach (['/dashboard', '/reports', '/reports/create', '/reports/'.$report->id, '/reports/'.$report->id.'/edit', '/guide'] as $path) {
            $this->actingAs($user)->get($path)->assertOk();
        }
        $this->actingAs($user)->get('/users')->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['/dashboard', '/users', '/users/create', '/users/'.$user->id.'/edit'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_web_authentication_and_registration(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->post('/register', ['name' => 'Warga', 'email' => 'warga@test.id', 'password' => 'Bersih123!', 'password_confirmation' => 'Bersih123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => 'warga@test.id', 'password' => 'Bersih123!'])->assertRedirect('/dashboard');
    }
}
