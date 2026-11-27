<?php

use App\Enums\RoleName;
use App\Models\Developer;
use App\Models\InstalmentPlanItem;
use App\Models\Project;
use App\Models\Shareholder;
use App\Models\User;
use App\Services\Erp\ShareSale;
use App\Services\LeadWorkflow;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

pest()->beforeEach(fn () => test()->seed(RolesAndPermissionsSeeder::class))->in('Feature');

/** Create a verified, active user holding the given role. */
function userWithRole(RoleName $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role->value);

    return $user;
}

// ---- Shared test helpers (CRM and Developer ERP) ----
function wf(): LeadWorkflow
{
    return app(LeadWorkflow::class);
}

function manager(): User
{
    return userWithRole(RoleName::SalesManager);
}

function agent(): User
{
    return userWithRole(RoleName::SalesAgent);
}

function erpDeveloper(): Developer
{
    return Developer::factory()->published()->create();
}

function erpUser(RoleName $role, ?Developer $dev = null): User
{
    return userWithRole($role, ['developer_id' => ($dev ?? erpDeveloper())->id]);
}

/** A share project with 5 shares priced 900,000 and a plan: booking 200,000 + 7 monthly instalments of 100,000. */
function erpProject(Developer $dev, int $shares = 5): Project
{
    $project = Project::factory()->published()->create(['developer_id' => $dev->id, 'is_share_project' => true]);
    $owner = userWithRole(RoleName::DeveloperOwner, ['developer_id' => $dev->id]);
    app(ShareSale::class)->generateShares($project, $shares, 1.5, 900_000, $owner);
    foreach ([['Booking', 200_000, 1, 0, 1, 0], ['Monthly instalment', 100_000, 7, 1, 1, 1]] as [$label, $amt, $count, $start, $interval, $sort]) {
        (new InstalmentPlanItem)->forceFill(['developer_id' => $dev->id, 'project_id' => $project->id, 'label' => $label, 'amount' => $amt, 'count' => $count, 'start_after_months' => $start, 'interval_months' => $interval, 'sort_order' => $sort])->save();
    }

    return $project->fresh();
}

function erpHolder(Developer $dev, string $name = 'Karim Hasan'): Shareholder
{
    $holder = (new Shareholder)->forceFill(['developer_id' => $dev->id, 'name' => $name, 'phone' => '+8801712345678', 'nid' => '1234 5678 9012']);
    $holder->save();

    return $holder;
}

/** Livewire component tests do not go through the panel routes, so tell Filament which panel is current. */
function erpPanel(): void
{
    Filament::setCurrentPanel(Filament::getPanel('erp'));
}
