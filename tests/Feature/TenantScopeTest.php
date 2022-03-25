<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_model_has_a_tenant_id_on_migration(){

        // Given
        $now = now();
        $filename = $now->format('Y_m_d_His').'_create_tests_table.php';
        $filePath = database_path('migrations/'.$filename);

        // When
        $this->artisan('make:model Test -m');

        // Then
        $this->assertTrue(File::exists($filePath));
        $this->assertStringContainsString('$table->unsignedBigInteger("tenant_id")->index();',
            File::get($filePath));

        File::delete($filePath);
        File::delete(app_path('Models/Test.php'));
    }

    /** @test */
    public function a_user_can_only_see_users_in_the_same_tenant(){

        // Given
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user1 = User::factory()->create([
            "tenant_id" => $tenant1
        ]);

        User::factory(9)->create([
            "tenant_id" => $tenant1
        ]);

        User::factory(10)->create([
            "tenant_id" => $tenant2
        ]);

        // When
        auth()->login($user1);

        // Then
        self::assertEquals(10, User::count());
    }

    /** @test */
    public function a_user_can_only_create_a_user_in_his_tenant_even_if_other_tenant_is_provided(){

        // Given
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->create([
            "tenant_id" => $tenant1
        ]);

        // When
        auth()->login($user1);
        $createdUser = User::factory()->make();
        $createdUser->tenant_id = $tenant2->id;
        $createdUser->save();

        // Then
        self::assertEquals($createdUser->tenant_id, $user1->tenant_id);
    }
}
