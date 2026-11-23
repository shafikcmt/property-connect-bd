<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\Favorites;
use Illuminate\Auth\Events\Login;

class MergeGuestFavorites
{
    public function handle(Login $event): void
    {
        $session = array_filter([
            'property' => request()->session()->get('fav.property'),
            'project' => request()->session()->get('fav.project'),
        ]);
        if ($session && $event->user instanceof User) {
            app(Favorites::class)->merge($event->user, $session);
            request()->session()->forget(['fav.property', 'fav.project']);
        }
    }
}
