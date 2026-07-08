<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration
     */
    public function test_register_successful(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test registration with missing required fields
     */
    public function test_register_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            // missing email and password
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test registration with invalid email format
     */
    public function test_register_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test registration with password mismatch
     */
    public function test_register_password_mismatch(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'DifferentPassword@123',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test registration with password too short
     */
    public function test_register_password_too_short(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Pass1',
            'password_confirmation' => 'Pass1',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test registration with duplicate email
     */
    public function test_register_duplicate_email(): void
    {
        // Create a user first
        User::create([
            'name' => 'Existing User',
            'email' => 'john@example.com',
            'password' => bcrypt('Password@123'),
        ]);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertFalse($response->json('success'));
    }

    /**
     * Test successful login
     */
    public function test_login_successful(): void
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password@123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'john@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test login with wrong password
     */
    public function test_login_invalid_password(): void
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password@123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'john@example.com',
            'password' => 'WrongPassword@123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login with non-existent email
     */
    public function test_login_non_existent_email(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login with missing email
     */
    public function test_login_missing_email(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'password' => 'Password@123',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test login with missing password
     */
    public function test_login_missing_password(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test getting profile successfully
     */
    public function test_profile_successful(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password@123'),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Profile fetched successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    }

    /**
     * Test profile without authentication token
     */
    public function test_profile_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    /**
     * Test profile with invalid token
     */
    public function test_profile_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    /**
     * Test logout successfully
     */
    public function test_logout_successful(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password@123'),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    /**
     * Test logout without authentication token
     */
    public function test_logout_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(401);
    }

    /**
     * Test complete authentication flow
     */
public function test_complete_auth_flow(): void
{
    // Step 1: Register
    $registerResponse = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password@456',
        'password_confirmation' => 'Password@456',
    ]);

    $registerResponse->assertStatus(201);

    $registerToken = $registerResponse->json('data.token');

    // Step 2: Access profile using registration token
    $profileResponse = $this->withHeader(
        'Authorization',
        "Bearer {$registerToken}"
    )->getJson('/api/v1/profile');

    $profileResponse->assertStatus(200)
        ->assertJson([
            'data' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ],
        ]);

    // Step 3: Logout
    $logoutResponse = $this->withHeader(
        'Authorization',
        "Bearer {$registerToken}"
    )->postJson('/api/v1/logout');

    $logoutResponse->assertStatus(200);

    // Step 4: Verify token was deleted
    $this->assertDatabaseCount('personal_access_tokens', 0);

    $this->assertNull(
        PersonalAccessToken::findToken($registerToken)
    );

    // Step 5: Login again
    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'Password@456',
    ]);

    $loginResponse->assertStatus(200);

    $newToken = $loginResponse->json('data.token');

    // Step 6: Verify new token works
    $newProfileResponse = $this->withHeader(
        'Authorization',
        "Bearer {$newToken}"
    )->getJson('/api/v1/profile');

    $newProfileResponse->assertStatus(200)
        ->assertJson([
            'data' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ],
        ]);
}

    /**
     * Test rate limiting on register
     */
    public function test_register_rate_limiting(): void
    {
        // This test assumes 'throttle:5,1' middleware (5 requests per 1 minute)
        // Making 6 requests should hit the rate limit
        
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/register', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/register', [
            'name' => 'User 6',
            'email' => 'user6@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Test rate limiting on login
     */
    public function test_login_rate_limiting(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('Password@123'),
        ]);

        // Making 6 requests should hit the rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }
}
