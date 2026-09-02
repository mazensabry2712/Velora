<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class CustomerIdentityBoundaryTest extends TenantTestCase
{
    #[Test]
    public function customer_can_be_linked_to_an_optional_login_account(): void
    {
        $user = User::create([
            'name' => 'Account Customer',
            'email' => 'account-customer@example.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Account',
            'last_name' => 'Customer',
            'email' => $user->email,
        ]);

        $this->assertSame($user->id, $customer->user_id);
        $this->assertTrue($customer->user->is($user));
        $this->assertTrue($user->customerProfile->is($customer));
    }

    #[Test]
    public function user_invoice_relation_resolves_through_customer_entity(): void
    {
        $user = User::create([
            'name' => 'Invoice Customer',
            'email' => 'invoice-customer@example.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Invoice',
            'last_name' => 'Customer',
            'email' => $user->email,
        ]);

        $invoice = Invoice::create([
            'number' => 'INV-IDENTITY-001',
            'customer_id' => $customer->id,
            'amount' => 100,
            'status' => 'pending',
        ]);

        $this->assertTrue($invoice->customer->is($customer));
        $this->assertTrue($customer->invoices->contains($invoice));
        $this->assertTrue($user->invoices->contains($invoice));
    }
}
