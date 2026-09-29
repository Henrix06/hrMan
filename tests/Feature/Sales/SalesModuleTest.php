<?php

namespace Tests\Feature\Sales;

use App\Exceptions\Sales\SalesException;
use App\Livewire\Sales\SalesOrderList;
use App\Livewire\Sales\TransportRequestList;
use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\PlanService;
use App\Services\SalesOrderService;
use App\Services\TenantProvisioner;
use App\Services\TransportRequestService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SalesModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);
    }

    /**
     * @return array{tenant: Tenant, user: User}
     */
    private function provisionCompany(string $email = 'owner@example.com'): array
    {
        return app(TenantProvisioner::class)->provision([
            'name' => 'Owner User',
            'email' => $email,
            'password' => 'password123',
            'company_name' => 'Acme '.$email,
            'plan' => 'free',
            'billing_cycle' => 'monthly',
            'payment_method' => ['type' => 'none'],
        ]);
    }

    private function makeUser(Tenant $tenant, string $role, string $name): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => Str::slug($name).'.'.Str::random(6).'@example.com',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<int, array<string, mixed>>|null  $items
     */
    private function logOrder(User $seller, array $overrides = [], ?array $items = null, string $status = 'draft'): SalesOrder
    {
        return app(SalesOrderService::class)->create($seller, array_merge([
            'customer_name' => 'Acme Retail',
            'customer_email' => 'buyer@acme.test',
            'customer_phone' => '+263 77 000 0000',
            'customer_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'notes' => null,
        ], $overrides), $items ?? [
            ['description' => 'Widget', 'quantity' => 2, 'unit_price' => 50],
        ], $status);
    }

    private function orders(): SalesOrderService
    {
        return app(SalesOrderService::class);
    }

    private function transport(): TransportRequestService
    {
        return app(TransportRequestService::class);
    }

    /* ---------------------------------------------------------------------
     |  Logging a sale
     * ------------------------------------------------------------------- */

    public function test_a_team_member_can_log_an_order_with_items(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($employee);

        $order = $this->logOrder($employee, [], [
            ['description' => 'Widget', 'quantity' => 2, 'unit_price' => 50],
            ['description' => 'Stand', 'quantity' => 1, 'unit_price' => 20],
        ]);

        $this->assertMatchesRegularExpression('/^SO-\d{4}-\d{5}$/', $order->reference);
        $this->assertSame('draft', $order->status);
        $this->assertSame($employee->id, $order->sold_by);
        $this->assertSame($tenant->id, $order->tenant_id);
        $this->assertSame(2, $order->items()->count());
        $this->assertEquals(120.00, (float) $order->total_amount);
        $this->assertSame('2 × Widget, 1 × Stand', $order->itemsSummary());
    }

    public function test_totals_apply_the_tax_rate(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);

        $order = $this->logOrder($owner, ['tax_rate' => 15], [
            ['description' => 'Widget', 'quantity' => 2, 'unit_price' => 50],
            ['description' => 'Freight', 'quantity' => 1, 'unit_price' => 10],
        ]);

        $this->assertEquals(110.00, (float) $order->subtotal);
        $this->assertEquals(16.50, (float) $order->tax_amount);
        $this->assertEquals(126.50, (float) $order->total_amount);
    }

    public function test_line_totals_are_stored_per_item(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);

        $order = $this->logOrder($owner, [], [
            ['description' => 'Widget', 'quantity' => 3, 'unit_price' => 19.99],
        ]);

        $this->assertEquals(59.97, (float) $order->items->first()->line_total);
    }

    public function test_an_order_without_items_is_rejected(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);

        $this->expectException(SalesException::class);

        $this->logOrder($owner, [], []);
    }

    public function test_an_order_can_be_logged_straight_as_confirmed(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);

        $order = $this->logOrder($owner, [], null, 'confirmed');

        $this->assertSame('confirmed', $order->status);
        $this->assertNotNull($order->confirmed_at);
    }

    public function test_a_draft_order_can_be_updated_and_the_totals_follow(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner);

        $updated = $this->orders()->update($order, [
            'customer_name' => 'New Customer',
            'customer_email' => '',
            'customer_phone' => '',
            'customer_address' => '',
            'delivery_address' => '',
            'order_date' => $order->order_date->toDateString(),
            'tax_rate' => 10,
            'notes' => '',
        ], [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100]], $owner);

        $this->assertSame('New Customer', $updated->customer_name);
        $this->assertEquals(100.00, (float) $updated->subtotal);
        $this->assertEquals(10.00, (float) $updated->tax_amount, 'The new tax rate is applied');
        $this->assertEquals(110.00, (float) $updated->total_amount);
        $this->assertSame(1, $updated->items()->count());
    }

    public function test_a_confirmed_order_cannot_be_edited(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner, [], null, 'confirmed');

        $this->expectException(SalesException::class);

        $this->orders()->update($order, [
            'customer_name' => 'Changed',
            'customer_email' => '',
            'customer_phone' => '',
            'customer_address' => '',
            'delivery_address' => '',
            'order_date' => $order->order_date->toDateString(),
            'tax_rate' => 0,
            'notes' => '',
        ], [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 10]], $owner);
    }

    public function test_references_do_not_repeat_within_a_company(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);

        $first = $this->logOrder($owner);
        $second = $this->logOrder($owner);

        $this->assertNotSame($first->reference, $second->reference);
    }

    /* ---------------------------------------------------------------------
     |  Order lifecycle
     * ------------------------------------------------------------------- */

    public function test_confirming_an_order_notifies_the_seller(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        $this->actingAs($owner);
        $this->orders()->confirm($order, $owner);

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertDatabaseHas('hr_notifications', [
            'user_id' => $seller->id,
            'type' => 'sales_order',
        ]);
    }

    public function test_an_order_cannot_be_confirmed_twice(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner, [], null, 'confirmed');

        $this->expectException(SalesException::class);

        $this->orders()->confirm($order, $owner);
    }

    public function test_a_draft_order_cannot_be_fulfilled(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner);

        $this->expectException(SalesException::class);

        $this->orders()->fulfill($order, $owner);
    }

    public function test_fulfilling_records_the_timestamp(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner, [], null, 'confirmed');

        $this->orders()->fulfill($order, $owner);

        $order->refresh();

        $this->assertSame('fulfilled', $order->status);
        $this->assertNotNull($order->fulfilled_at);
    }

    public function test_a_fulfilled_order_cannot_be_cancelled(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner, [], null, 'confirmed');
        $this->orders()->fulfill($order, $owner);

        $this->expectException(SalesException::class);

        $this->orders()->cancel($order->fresh(), $owner);
    }

    /* ---------------------------------------------------------------------
     |  Who sees which orders
     * ------------------------------------------------------------------- */

    public function test_an_employee_only_sees_their_own_orders(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $other = $this->makeUser($tenant, 'Employee', 'Other Seller');

        $this->actingAs($seller);
        $mine = $this->logOrder($seller);

        $this->actingAs($other);
        $theirs = $this->logOrder($other, ['customer_name' => 'Someone Else']);

        Livewire::actingAs($seller)
            ->test(SalesOrderList::class)
            ->assertSee($mine->reference)
            ->assertDontSee($theirs->reference);
    }

    public function test_a_manager_sees_every_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $manager = $this->makeUser($tenant, 'Manager', 'Mary Manager');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        Livewire::actingAs($manager)
            ->test(SalesOrderList::class)
            ->assertSee($order->reference);
    }

    public function test_the_orders_and_transport_pages_are_reachable(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner)->get(route('sales.orders'))->assertOk()->assertSee('Sales Orders');
        $this->actingAs($owner)->get(route('sales.transport'))->assertOk()->assertSee('Transport Requests');
    }

    public function test_the_sales_pages_require_authentication(): void
    {
        $this->get(route('sales.orders'))->assertRedirect(route('login'));
        $this->get(route('sales.transport'))->assertRedirect(route('login'));
    }

    /* ---------------------------------------------------------------------
     |  Logging through the page
     * ------------------------------------------------------------------- */

    public function test_the_page_logs_a_draft_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        Livewire::actingAs($employee)
            ->test(SalesOrderList::class)
            ->call('openCreate')
            ->set('customer_name', 'Acme Retail')
            ->set('delivery_address', '99 Customer Avenue')
            ->set('items', [['description' => 'Widget', 'quantity' => '2', 'unit_price' => '50']])
            ->call('save', 'draft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales_orders', [
            'customer_name' => 'Acme Retail',
            'sold_by' => $employee->id,
            'status' => 'draft',
        ]);
    }

    public function test_the_page_can_confirm_an_order_while_logging_it(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        Livewire::actingAs($employee)
            ->test(SalesOrderList::class)
            ->call('openCreate')
            ->set('customer_name', 'Acme Retail')
            ->set('items', [['description' => 'Widget', 'quantity' => '1', 'unit_price' => '25']])
            ->call('save', 'confirmed')
            ->assertHasNoErrors();

        $this->assertSame('confirmed', SalesOrder::first()->status);
    }

    public function test_the_page_rejects_an_order_with_no_items(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        Livewire::actingAs($employee)
            ->test(SalesOrderList::class)
            ->call('openCreate')
            ->set('customer_name', 'Acme Retail')
            ->set('items', [['description' => '', 'quantity' => '1', 'unit_price' => '']])
            ->call('save', 'draft')
            ->assertHasErrors(['items' => 'required']);

        $this->assertSame(0, SalesOrder::count());
    }

    public function test_an_employee_cannot_confirm_an_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($employee);
        $order = $this->logOrder($employee);

        Livewire::actingAs($employee)
            ->test(SalesOrderList::class)
            ->call('confirm', $order->id)
            ->assertForbidden();

        $this->assertSame('draft', $order->fresh()->status);
    }

    public function test_an_employee_cannot_open_another_employees_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $other = $this->makeUser($tenant, 'Employee', 'Other Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        Livewire::actingAs($other)
            ->test(SalesOrderList::class)
            ->call('view', $order->id)
            ->assertNotFound();

        Livewire::actingAs($other)
            ->test(SalesOrderList::class)
            ->assertDontSee($order->reference);
    }

    /* ---------------------------------------------------------------------
     |  Requesting transport
     * ------------------------------------------------------------------- */

    public function test_an_employee_can_request_transport_for_their_confirmed_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');

        $request = $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '+263 77 000 0000',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => now()->addDay()->toDateString(),
            'notes' => 'Deliver before noon',
        ]);

        $this->assertMatchesRegularExpression('/^TR-\d{4}-\d{5}$/', $request->reference);
        $this->assertSame('pending', $request->status);
        $this->assertSame($order->id, $request->sales_order_id);
        $this->assertSame($seller->id, $request->requested_by);
        $this->assertSame('2 × Widget', $request->items_summary, 'The sold items are carried onto the request');
        $this->assertTrue($order->hasOpenTransportRequest());
    }

    public function test_transport_cannot_be_requested_before_the_order_is_confirmed(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        $this->expectException(SalesException::class);

        $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);
    }

    public function test_transport_cannot_be_requested_for_someone_elses_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $other = $this->makeUser($tenant, 'Employee', 'Other Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');

        $this->actingAs($other);

        $this->expectException(SalesException::class);

        $this->transport()->requestFromOrder($order, $other, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);
    }

    public function test_only_one_open_transport_request_per_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');

        $payload = [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ];

        $this->transport()->requestFromOrder($order, $seller, $payload);

        $this->expectException(SalesException::class);

        $this->transport()->requestFromOrder($order->fresh(), $seller, $payload);
    }

    public function test_a_new_request_is_allowed_once_the_previous_one_is_cancelled(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');

        $payload = [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ];

        $first = $this->transport()->requestFromOrder($order, $seller, $payload);
        $this->transport()->cancel($first, $seller);

        $second = $this->transport()->requestFromOrder($order->fresh(), $seller, $payload);

        $this->assertNotSame($first->reference, $second->reference);
        $this->assertSame('cancelled', $first->fresh()->status);
    }

    public function test_requesting_transport_notifies_the_transport_managers(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');

        $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->assertDatabaseHas('hr_notifications', [
            'user_id' => $owner->id,
            'type' => 'transport_requested',
        ]);
    }

    public function test_the_transport_page_requests_a_move_through_the_form(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');

        Livewire::actingAs($seller)
            ->test(TransportRequestList::class)
            ->call('startFromOrder', $order->id)
            ->assertSet('showModal', true)
            ->assertSet('order_id', $order->id)
            ->assertSet('delivery_address', '99 Customer Avenue')
            ->set('pickup_address', '1 Warehouse Road')
            ->call('requestTransport')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('transport_requests', [
            'sales_order_id' => $order->id,
            'requested_by' => $seller->id,
            'status' => 'pending',
        ]);
    }

    public function test_the_orders_page_deep_links_into_a_prefilled_request(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');

        $this->actingAs($seller)
            ->get(route('sales.transport', ['order' => $order->id]))
            ->assertOk()
            ->assertSee($order->reference);

        $this->actingAs($seller)
            ->get(route('sales.transport'))
            ->assertOk()
            ->assertDontSee($order->reference);
    }

    /* ---------------------------------------------------------------------
     |  Running the transport desk
     * ------------------------------------------------------------------- */

    public function test_approving_assigns_a_driver_and_tells_the_requester(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $driver = $this->makeUser($tenant, 'Employee', 'Dan Driver');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');
        $request = $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->actingAs($owner);
        $this->transport()->approve($request, $owner, [
            'driver_id' => $driver->id,
            'vehicle' => 'Truck ABC 1234',
            'scheduled_date' => now()->addDay()->toDateString(),
            'notes' => '',
        ]);

        $request->refresh();

        $this->assertSame('approved', $request->status);
        $this->assertSame($driver->id, $request->driver_id);
        $this->assertSame('Truck ABC 1234', $request->vehicle);
        $this->assertSame($owner->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);

        $this->assertDatabaseHas('hr_notifications', [
            'user_id' => $seller->id,
            'type' => 'transport_status',
        ]);
    }

    public function test_approval_requires_a_driver(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');
        $request = $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->actingAs($owner);

        $this->expectException(SalesException::class);

        $this->transport()->approve($request, $owner, [
            'driver_id' => null,
            'vehicle' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);
    }

    public function test_a_request_moves_from_the_desk_to_the_customer(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $driver = $this->makeUser($tenant, 'Employee', 'Dan Driver');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');
        $request = $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->actingAs($owner);
        $this->transport()->approve($request, $owner, [
            'driver_id' => $driver->id,
            'vehicle' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->transport()->dispatch($request->fresh(), $owner);
        $this->assertSame('in_transit', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->dispatched_at);

        $this->transport()->deliver($request->fresh(), $owner);
        $this->assertSame('delivered', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->delivered_at);
        $this->assertFalse($request->fresh()->isOpen());
    }

    public function test_a_delivery_cannot_be_recorded_before_dispatch(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');
        $request = $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->actingAs($owner);

        $this->expectException(SalesException::class);

        $this->transport()->deliver($request, $owner);
    }

    public function test_an_employee_cannot_approve_transport(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $driver = $this->makeUser($tenant, 'Employee', 'Dan Driver');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');
        $request = $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        Livewire::actingAs($seller)
            ->test(TransportRequestList::class)
            ->call('openApprove', $request->id)
            ->assertForbidden();
    }

    public function test_the_requester_can_cancel_their_own_pending_request(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller, [], null, 'confirmed');
        $request = $this->transport()->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        Livewire::actingAs($seller)
            ->test(TransportRequestList::class)
            ->call('cancel', $request->id)
            ->assertHasNoErrors();

        $this->assertSame('cancelled', $request->fresh()->status);
    }

    /* ---------------------------------------------------------------------
     |  Company isolation and module wiring
     * ------------------------------------------------------------------- */

    public function test_another_companys_data_is_out_of_reach(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        ['user' => $stranger] = $this->provisionCompany('stranger@example.com');

        $this->actingAs($owner);
        $order = $this->logOrder($owner, [], null, 'confirmed');

        $this->actingAs($stranger);

        $this->assertNull(SalesOrder::find($order->id));
        $this->assertSame(0, SalesOrder::count());
    }

    public function test_the_sales_module_is_registered_and_part_of_the_paid_plans(): void
    {
        $this->assertArrayHasKey('sales', config('pricing.modules'));
        $this->assertContains('sales', config('pricing.plans.professional.modules'));
        $this->assertContains('sales', config('pricing.plans.enterprise.modules'));
        $this->assertNotContains('sales', config('pricing.plans.free.modules'));
    }

    public function test_switching_to_a_paid_plan_enables_the_sales_module(): void
    {
        ['tenant' => $tenant] = $this->provisionCompany();

        app(PlanService::class)->switchPlan($tenant, 'professional');

        $this->assertTrue((bool) TenantModule::where('tenant_id', $tenant->id)
            ->where('module_key', 'sales')
            ->value('is_enabled'));
    }

    public function test_the_seeded_roles_share_the_selling_permissions(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $sellable = ['view sales', 'create sales order', 'request transport'];

        $employee = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $manager = $this->makeUser($tenant, 'Manager', 'Mary Manager');

        foreach ($sellable as $permission) {
            $this->assertTrue($employee->hasPermissionTo($permission), "Employees can {$permission}");
            $this->assertTrue($manager->hasPermissionTo($permission), "Managers can {$permission}");
            $this->assertTrue($owner->hasPermissionTo($permission), "The workspace owner can {$permission}");
            $this->assertTrue($owner->hasPermissionTo('manage sales'));
            $this->assertTrue($owner->hasPermissionTo('manage transport'));
        }

        $this->assertFalse($employee->hasPermissionTo('manage sales'));
        $this->assertFalse($employee->hasPermissionTo('manage transport'));
        $this->assertTrue($manager->hasPermissionTo('manage sales'));
    }
}
