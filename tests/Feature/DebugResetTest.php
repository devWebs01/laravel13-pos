<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DebugResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_password_reset(): void
    {
        $user = User::factory()->create();
        $response = $this->post(route('password.request'), ['email' => $user->email]);
        $response->assertSessionHasNoErrors();
        $content = $response->getContent();
        echo "\nResponse status: " . $response->getStatusCode() . "\n";
        echo "Redirect to: " . ($response->isRedirect() ? $response->headers->get('Location') : 'not a redirect') . "\n";
        $status = session('status');
        echo "Session status: " . ($status ?? 'null') . "\n";
    }

    public function test_with_notification_fake(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $response = $this->post(route('password.request'), ['email' => $user->email]);
        $response->assertSessionHasNoErrors();
        echo "\n--- With Notification::fake() ---\n";
        echo "Response status: " . $response->getStatusCode() . "\n";
        $status = session('status');
        echo "Session status: " . ($status ?? 'null') . "\n";

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }
}
