<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('registers a buyer, assigns only the buyer role and asks for email verification', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Rahim Uddin', 'email' => 'Rahim@Example.com', 'phone' => '+8801711000000',
        'password' => 'correct-horse-9', 'password_confirmation' => 'correct-horse-9',
        // hostile extras must be ignored
        'status' => 'suspended', 'roles' => ['super-admin'],
    ])->assertRedirect(route('verification.notice'));

    $user = User::firstWhere('email', 'rahim@example.com');
    expect($user)->not->toBeNull()
        ->and($user->getRoleNames()->all())->toBe([RoleName::Buyer->value])
        ->and($user->isActive())->toBeTrue();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rejects weak passwords and duplicate emails on registration', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post('/register', ['name' => 'A', 'email' => 'taken@example.com', 'password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrors(['email', 'password']);
});

it('logs in with valid credentials and redirects to the account area', function () {
    $user = User::factory()->create(['email' => 'a@example.com']);

    $this->post('/login', ['email' => 'A@example.com', 'password' => 'password-1234'])->assertRedirect(route('account.index'));
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects wrong credentials and throttles after five attempts', function () {
    User::factory()->create(['email' => 'a@example.com']);

    foreach (range(1, 5) as $_) {
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
    }
    $this->post('/login', ['email' => 'a@example.com', 'password' => 'password-1234'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('blocks suspended users from the account area', function () {
    $user = User::factory()->suspended()->create();

    $this->actingAs($user)->get('/account')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('logs out', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect(route('home'));
    $this->assertGuest();
});

it('requires a verified email for the account area', function () {
    $this->actingAs(User::factory()->unverified()->create())->get('/account')->assertRedirect(route('verification.notice'));
});

it('verifies email through a signed link', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->actingAs($user)->get($url)->assertRedirect(route('account.index'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('resets a password and does not reveal whether an email exists', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status');
    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
        $this->post('/reset-password', [
            'token' => $n->token, 'email' => $user->email, 'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1',
        ])->assertRedirect(route('login'));

        return true;
    });
    expect(Hash::check('brand-new-pass-1', $user->fresh()->password))->toBeTrue();
});

it('redirects guests away from the account area', function () {
    $this->get('/account')->assertRedirect(route('login'));
});

it('does not allow the DB to store an invalid user status', function () {
    expect(fn () => DB::table('users')->where('id', User::factory()->create()->id)->update(['status' => 'bogus']))
        ->toThrow(QueryException::class);
});
