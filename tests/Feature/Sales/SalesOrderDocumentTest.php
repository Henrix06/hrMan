<?php

namespace Tests\Feature\Sales;

use App\Livewire\Sales\TransportRequestList;
use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Models\TransportRequest;
use App\Models\User;
use App\Services\SalesOrderService;
use App\Services\TenantProvisioner;
use App\Services\TransportRequestService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SalesOrderDocumentTest extends TestCase
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
     */
    private function logOrder(User $seller, array $overrides = [], string $status = 'confirmed'): SalesOrder
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
        ], $overrides), [
            ['description' => 'Widget', 'quantity' => 2, 'unit_price' => 50],
            ['description' => 'Stand', 'quantity' => 1, 'unit_price' => 10],
        ], $status);
    }

    private function documentUrl(SalesOrder $order): string
    {
        return route('sales.orders.document', $order);
    }

    /* ---------------------------------------------------------------------
     |  Printing an order
     * ------------------------------------------------------------------- */

    public function test_the_seller_can_print_their_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        $this->get($this->documentUrl($order))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee('Sales Order')
            ->assertSee('Acme Retail')
            ->assertSee('Widget')
            ->assertSee('Stand')
            ->assertSee('Sam Seller');
    }

    public function test_the_document_shows_the_totals_and_the_tax_rate(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner, ['tax_rate' => 15]);

        $this->get($this->documentUrl($order))
            ->assertOk()
            ->assertSee('USD 110.00')   // subtotal
            ->assertSee('USD 16.50')    // tax at 15%
            ->assertSee('USD 126.50')   // total
            ->assertSee('15%');
    }

    public function test_the_document_uses_the_order_currency(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner);
        $order->update(['currency' => 'ZWL']);

        $this->get($this->documentUrl($order->fresh()))
            ->assertOk()
            ->assertSee('ZWL 110.00');
    }

    public function test_a_draft_order_is_marked_as_a_draft(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner, [], 'draft');

        $this->get($this->documentUrl($order))
            ->assertOk()
            ->assertSee('Draft')
            ->assertSee('has not been confirmed yet');
    }

    public function test_the_document_offers_a_print_control_and_no_app_chrome(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner);

        $this->get($this->documentUrl($order))
            ->assertOk()
            ->assertSee('Print / Save as PDF')
            ->assertSee('window.print()', escape: false)
            ->assertDontSee('Log Out')
            ->assertDontSee('Plan &amp; Modules');
    }

    public function test_the_document_carries_the_sales_order_reference_in_the_page_title(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner);

        $this->get($this->documentUrl($order))
            ->assertOk()
            ->assertSee('<title>Sales Order '.$order->reference.'</title>', escape: false);
    }

    public function test_the_document_lists_the_arranged_transport(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $driver = $this->makeUser($tenant, 'Employee', 'Dan Driver');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        $request = app(TransportRequestService::class)->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->actingAs($owner);
        app(TransportRequestService::class)->approve($request, $owner, [
            'driver_id' => $driver->id,
            'vehicle' => 'Truck ABC 1234',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->actingAs($seller);

        $this->get($this->documentUrl($order))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('Dan Driver')
            ->assertSee('Truck ABC 1234')
            ->assertSee('99 Customer Avenue');
    }

    public function test_the_document_handles_an_order_with_no_transport_yet(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner);

        $this->get($this->documentUrl($order))
            ->assertOk()
            ->assertSee('No transport has been arranged for this order.');
    }

    /* ---------------------------------------------------------------------
     |  Who may print
     * ------------------------------------------------------------------- */

    public function test_a_manager_can_print_another_persons_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $manager = $this->makeUser($tenant, 'Manager', 'Mary Manager');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        $this->actingAs($manager)->get($this->documentUrl($order))->assertOk();
    }

    public function test_an_employee_cannot_print_someone_elses_order(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $other = $this->makeUser($tenant, 'Employee', 'Other Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        $this->actingAs($other)->get($this->documentUrl($order))->assertNotFound();
    }

    public function test_another_company_cannot_print_the_order(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        ['user' => $stranger] = $this->provisionCompany('stranger@example.com');

        $this->actingAs($owner);
        $order = $this->logOrder($owner);

        $this->actingAs($stranger)->get($this->documentUrl($order))->assertNotFound();
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner);

        auth()->logout();

        $this->get($this->documentUrl($order))->assertRedirect(route('login'));
    }

    public function test_the_orders_page_links_to_the_document(): void
    {
        ['user' => $owner] = $this->provisionCompany();

        $this->actingAs($owner);
        $order = $this->logOrder($owner);

        $this->actingAs($owner)
            ->get(route('sales.orders'))
            ->assertOk()
            ->assertSee($this->documentUrl($order), escape: false);
    }

    /* ---------------------------------------------------------------------
     |  Drivers can open what they are driving
     * ------------------------------------------------------------------- */

    public function test_the_driver_can_see_the_request_they_are_driving(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $driver = $this->makeUser($tenant, 'Employee', 'Dan Driver');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        $request = app(TransportRequestService::class)->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->actingAs($owner);
        app(TransportRequestService::class)->approve($request, $owner, [
            'driver_id' => $driver->id,
            'vehicle' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        Livewire::actingAs($driver)
            ->test(TransportRequestList::class)
            ->assertSee($request->fresh()->reference)
            ->assertSee('99 Customer Avenue');

        // The driver can look, but not run the desk.
        Livewire::actingAs($driver)
            ->test(TransportRequestList::class)
            ->call('markInTransit', $request->id)
            ->assertForbidden();

        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_an_unrelated_employee_still_cannot_see_a_transport_request(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $seller = $this->makeUser($tenant, 'Employee', 'Sam Seller');
        $other = $this->makeUser($tenant, 'Employee', 'Other Seller');

        $this->actingAs($seller);
        $order = $this->logOrder($seller);

        $request = app(TransportRequestService::class)->requestFromOrder($order, $seller, [
            'pickup_address' => '1 Warehouse Road',
            'delivery_address' => '99 Customer Avenue',
            'pickup_contact' => '',
            'delivery_contact' => '',
            'items_summary' => '',
            'scheduled_date' => null,
            'notes' => '',
        ]);

        $this->assertSame(1, TransportRequest::count());

        Livewire::actingAs($other)
            ->test(TransportRequestList::class)
            ->assertDontSee($request->reference);
    }
}
