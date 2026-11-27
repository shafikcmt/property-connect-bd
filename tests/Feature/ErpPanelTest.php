<?php

use App\Enums\PaymentMethod;
use App\Enums\RoleName;
use App\Filament\Erp\Resources\DeedBatches\Pages\ListDeedBatches;
use App\Filament\Erp\Resources\DeedBatches\Pages\ViewDeedBatch;
use App\Filament\Erp\Resources\FlatAllocations\Pages\ListFlatAllocations;
use App\Filament\Erp\Resources\Projects\Pages\ViewErpProject;
use App\Filament\Erp\Resources\Projects\RelationManagers\MilestonesRelationManager;
use App\Filament\Erp\Resources\Projects\RelationManagers\SharesRelationManager;
use App\Filament\Erp\Resources\Shareholders\Pages\CreateShareholder;
use App\Filament\Erp\Resources\Shareholders\Pages\EditShareholder;
use App\Filament\Erp\Resources\ShareHoldings\Pages\ListShareHoldings;
use App\Filament\Erp\Resources\ShareHoldings\Pages\ViewShareHolding;
use App\Filament\Erp\Resources\ShareHoldings\RelationManagers\PaymentsRelationManager;
use App\Filament\Erp\Widgets\ErpStats;
use App\Filament\Erp\Widgets\OverdueHoldings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Payment;
use App\Models\ProjectShare;
use App\Models\Shareholder;
use App\Models\ShareHolding;
use App\Models\User;
use App\Services\Erp\ConstructionLog;
use App\Services\Erp\DeedRegistry;
use App\Services\Erp\PaymentRecorder;
use App\Services\Erp\ShareSale;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/** A company with one project, a sold share and some payments, plus its owner signed in. */
function erpWorld(): array
{
    $dev = erpDeveloper();
    $project = erpProject($dev, 4);
    $owner = erpUser(RoleName::DeveloperOwner, $dev);
    $holder = erpHolder($dev);
    $holding = app(ShareSale::class)->sell(ProjectShare::where('project_id', $project->id)->first(), $holder, 900_000, now()->subMonths(3)->startOfDay(), $owner);
    $payment = app(PaymentRecorder::class)->record($holding, 250_000, now()->subMonths(2), PaymentMethod::Bkash, 'TX-9', null, $owner);

    return compact('dev', 'project', 'owner', 'holder', 'holding', 'payment');
}

it('shows each role the right ERP screens', function () {
    ['dev' => $dev, 'project' => $project, 'holding' => $holding] = erpWorld();
    $urls = ['/erp', '/erp/projects', "/erp/projects/{$project->id}", '/erp/shareholders', '/erp/share-holdings', "/erp/share-holdings/{$holding->id}", '/erp/payments'];

    $this->actingAs(erpUser(RoleName::DeveloperOwner, $dev));
    foreach ($urls as $url) {
        expect($this->get($url)->status())->toBe(200, "owner {$url}");
    }

    $this->actingAs(erpUser(RoleName::DeveloperAccountant, $dev));
    foreach ($urls as $url) {
        expect($this->get($url)->status())->toBe(200, "accountant {$url}");
    }
    $this->get('/erp/shareholders/create')->assertForbidden();

    $this->actingAs(erpUser(RoleName::DeveloperSales, $dev));
    foreach (['/erp', '/erp/projects', '/erp/shareholders', '/erp/shareholders/create', '/erp/share-holdings'] as $url) {
        $this->get($url)->assertOk();
    }
    $this->get('/erp/payments')->assertForbidden();
});

it('never shows one company’s records to another', function () {
    erpPanel();
    ['holding' => $holding, 'holder' => $holder, 'payment' => $payment, 'project' => $project] = erpWorld();
    $other = erpUser(RoleName::DeveloperOwner);

    $this->actingAs($other);
    $this->get("/erp/share-holdings/{$holding->id}")->assertNotFound();
    $this->get("/erp/shareholders/{$holder->id}")->assertNotFound();
    $this->get("/erp/projects/{$project->id}")->assertNotFound();
    $this->get("/erp/receipts/{$payment->id}")->assertNotFound();
    $this->get("/erp/statements/{$holding->id}")->assertNotFound();
    Livewire::test(ListShareHoldings::class)->assertCountTableRecords(0);
});

it('creates and edits a shareholder without ever showing the full NID', function () {
    erpPanel();
    ['owner' => $owner] = erpWorld();
    $this->actingAs($owner);

    Livewire::test(CreateShareholder::class)->fillForm(['name' => 'Rina Akter', 'phone' => '01812345678', 'nid' => '9876 5432 1098'])->call('create')->assertHasNoFormErrors();
    $h = Shareholder::where('name', 'Rina Akter')->firstOrFail();
    expect($h->developer_id)->toBe($owner->developer_id)->and($h->maskedNid())->toBe('••••••1098')->and($h->created_by)->toBe($owner->id);

    $page = Livewire::test(EditShareholder::class, ['record' => $h->id]);
    $page->assertSet('data.nid', null)->fillForm(['name' => 'Rina A.'])->call('save')->assertHasNoFormErrors();
    expect($h->fresh()->nid)->toBe('9876 5432 1098')->and($h->fresh()->name)->toBe('Rina A.');
    $this->get("/erp/shareholders/{$h->id}")->assertOk()->assertSee('••••••1098')->assertDontSee('9876 5432');
});

it('adds shares and sells one from the project page, enforcing the rules in the dialog', function () {
    erpPanel();
    ['owner' => $owner, 'project' => $project, 'holder' => $holder] = erpWorld();
    $this->actingAs($owner);
    $before = ProjectShare::where('project_id', $project->id)->count();

    $page = Livewire::test(SharesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewErpProject::class]);
    $page->callAction(TestAction::make('generateShares')->table(), data: ['count' => 2, 'area' => 1.2, 'price' => 950_000]);
    expect(ProjectShare::where('project_id', $project->id)->count())->toBe($before + 2)->and($project->fresh()->total_shares)->toBe($before + 2);

    $share = ProjectShare::where('project_id', $project->id)->where('status', 'available')->first();
    $page->callTableAction('sell', $share, data: ['shareholder_id' => $holder->id, 'price' => 880_000, 'sold_on' => now()->toDateString()]);
    expect($share->fresh()->status->value)->toBe('sold')->and(ShareHolding::where('project_share_id', $share->id)->value('price'))->toEqual(880_000);

    $page->assertTableActionHidden('sell', $share->fresh());
});

it('records and reverses payments from the sale page', function () {
    erpPanel();
    ['owner' => $owner, 'holding' => $holding, 'payment' => $payment] = erpWorld();
    $this->actingAs($owner);

    $view = Livewire::test(ViewShareHolding::class, ['record' => $holding->id]);
    $view->callAction('recordPayment', data: ['amount' => 100_000, 'received_on' => now()->toDateString(), 'method' => 'cash'])->assertNotified('Payment recorded');
    expect(Payment::where('share_holding_id', $holding->id)->count())->toBe(2);

    $view->callAction('recordPayment', data: ['amount' => 9_000_000, 'received_on' => now()->toDateString(), 'method' => 'cash'])->assertNotified();
    expect(Payment::where('share_holding_id', $holding->id)->count())->toBe(2);

    Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $holding, 'pageClass' => ViewShareHolding::class])->assertCountTableRecords(2)
        ->callTableAction('reverse', $payment, data: ['reason' => 'Wrong share'])->assertHasNoTableActionErrors();
    expect(Payment::where('reverses_payment_id', $payment->id)->exists())->toBeTrue();

    Livewire::test(ViewShareHolding::class, ['record' => $holding->id])->callAction('cancelSale', data: ['reason' => 'Buyer withdrew']);
    expect($holding->fresh()->status)->toBe('cancelled');
});

it('lets the accountant record payments but not reverse them or cancel sales', function () {
    erpPanel();
    ['dev' => $dev, 'holding' => $holding, 'payment' => $payment] = erpWorld();
    $this->actingAs(erpUser(RoleName::DeveloperAccountant, $dev));

    Livewire::test(ViewShareHolding::class, ['record' => $holding->id])->assertActionVisible('recordPayment')->assertActionHidden('cancelSale');
    Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $holding, 'pageClass' => ViewShareHolding::class])->assertTableActionHidden('reverse', $payment);
});

it('prints receipts and statements for the right people', function () {
    ['dev' => $dev, 'owner' => $owner, 'holding' => $holding, 'payment' => $payment] = erpWorld();

    $this->get("/erp/receipts/{$payment->id}")->assertRedirect('/login');

    $this->actingAs($owner);
    $this->get("/erp/receipts/{$payment->id}")->assertOk()->assertSee($payment->receipt_no)->assertSee('250,000.00')->assertSee('TX-9')->assertSee('Karim Hasan');
    $this->get("/erp/statements/{$holding->id}")->assertOk()->assertSee('Instalments')->assertSee('••••••9012')->assertDontSee('1234 5678')->assertSee($payment->receipt_no);

    $this->actingAs(erpUser(RoleName::DeveloperSales, $dev));
    $this->get("/erp/receipts/{$payment->id}")->assertForbidden();
    $this->get("/erp/statements/{$holding->id}")->assertOk()->assertDontSee($payment->receipt_no);
});

it('shows company numbers and who is behind on the dashboard', function () {
    erpPanel();
    ['owner' => $owner] = erpWorld();
    $this->actingAs($owner);

    Livewire::test(ErpStats::class)->assertSee('Shares sold')->assertSee('1 of 4')->assertSee('250,000')->assertSee('Overdue');
    Livewire::test(OverdueHoldings::class)->assertSee('Karim Hasan');

    $this->actingAs(erpUser(RoleName::DeveloperSales, $owner->developer));
    Livewire::test(ErpStats::class)->assertSee('Shares sold')->assertDontSee('Collected this month');
    expect(OverdueHoldings::canView())->toBeFalse();
});

it('lets platform admins create developer accounts safely and mark the email verified', function () {
    $dev = erpDeveloper();
    $admin = userWithRole(RoleName::Admin);
    $this->actingAs($admin);
    $roleId = fn (RoleName $r) => Role::where('name', $r->value)->value('id');

    Livewire::test(CreateUser::class)->fillForm(['name' => 'Acc Ountant', 'email' => 'acc@example.test', 'password' => 'Long-Passw0rd-12', 'status' => 'active', 'roles' => [$roleId(RoleName::DeveloperAccountant)]])
        ->call('create')->assertHasErrors(['data.developer_id']);

    Livewire::test(CreateUser::class)->fillForm(['name' => 'Acc Ountant', 'email' => 'acc@example.test', 'password' => 'Long-Passw0rd-12', 'status' => 'active', 'developer_id' => $dev->id, 'roles' => [$roleId(RoleName::DeveloperAccountant), $roleId(RoleName::Admin)]])
        ->call('create')->assertHasErrors(['data.roles']);

    Livewire::test(CreateUser::class)->fillForm(['name' => 'Staff Member', 'email' => 'staff@example.test', 'password' => 'Long-Passw0rd-12', 'status' => 'active', 'developer_id' => $dev->id, 'roles' => [$roleId(RoleName::DeveloperAccountant)]])
        ->call('create')->assertHasNoErrors();
    $user = User::where('email', 'staff@example.test')->firstOrFail();
    expect($user->developer_id)->toBe($dev->id)->and($user->hasVerifiedEmail())->toBeFalse();

    Livewire::test(CreateUser::class)->fillForm(['name' => 'Wrong', 'email' => 'wrong@example.test', 'password' => 'Long-Passw0rd-12', 'status' => 'active', 'developer_id' => $dev->id, 'roles' => [$roleId(RoleName::SalesAgent)]])
        ->call('create')->assertHasErrors(['data.developer_id']);

    Livewire::test(EditUser::class, ['record' => $user->id])->callAction('markVerified');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    config(['pcbd.admin_mfa_required' => true]);
    $this->actingAs($user->fresh())->get('/erp')->assertRedirect(); // accountant must enrol 2FA first
    config(['pcbd.admin_mfa_required' => false]);
    $this->actingAs($user->fresh())->get('/erp')->assertOk();

});

it('shows the deeds screens to the owner only for their own company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $owner = erpUser(RoleName::DeveloperOwner);
    $project = erpProject($owner->developer);
    $batch = app(DeedRegistry::class)->createBatch($project, null, $owner);
    $other = erpUser(RoleName::DeveloperOwner);

    $this->actingAs($owner);
    erpPanel();
    Livewire::test(ListDeedBatches::class)->assertSuccessful()->assertCanSeeTableRecords([$batch]);
    Livewire::test(ViewDeedBatch::class, ['record' => $batch->getKey()])->assertSuccessful();

    $this->actingAs($other);
    Livewire::test(ListDeedBatches::class)->assertCanNotSeeTableRecords([$batch]);
});

it('renders flats and construction screens for the owner and hides them from sales staff actions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $owner = erpUser(RoleName::DeveloperOwner);
    $project = erpProject($owner->developer);
    $m = app(ConstructionLog::class)->add($project, 'Foundation', 10, now()->subDay(), null, false, $owner);

    $this->actingAs($owner);
    erpPanel();
    Livewire::test(ListFlatAllocations::class)->assertSuccessful()->assertActionVisible('allocate');
    Livewire::test(MilestonesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewErpProject::class])->assertSuccessful()->assertCanSeeTableRecords([$m]);

    $this->actingAs(erpUser(RoleName::DeveloperSales, $owner->developer));
    Livewire::test(ListFlatAllocations::class)->assertActionHidden('allocate');
});
