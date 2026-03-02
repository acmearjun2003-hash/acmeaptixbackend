<?php

namespace App\Console\Commands;

use App\Models\EncCustomerMaster;
use Illuminate\Console\Command;

class TestCustomerEncryption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:test-encryption {customer_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test encryption/decryption on customer records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $customerId = $this->argument('customer_id');

        if ($customerId) {
            $this->testSingleCustomer($customerId);
        } else {
            $this->testRandomCustomers();
        }

        return 0;
    }

    /**
     * Test a single customer
     */
    private function testSingleCustomer($id)
    {
        $customer = EncCustomerMaster::find($id);

        if (!$customer) {
            $this->error("Customer with ID {$id} not found");
            return;
        }

        $this->info("Testing customer ID: {$customer->id}");
        $this->line('');

        $this->displayCustomerData($customer);
    }

    /**
     * Test random customers
     */
    private function testRandomCustomers()
    {
        $this->info('Testing 5 random customers...');
        $this->line('');

        $customers = EncCustomerMaster::inRandomOrder()->limit(5)->get();

        if ($customers->isEmpty()) {
            $this->error('No customers found in database');
            return;
        }

        foreach ($customers as $customer) {
            $this->displayCustomerData($customer);
            $this->line('');
            $this->line('-----------------------------------');
            $this->line('');
        }
    }

    /**
     * Display customer data with encryption/decryption
     */
    private function displayCustomerData($customer)
    {
        $this->table(
            ['Field', 'Encrypted (HEX)', 'Decrypted'],
            [
                [
                    'ID',
                    $customer->id,
                    $customer->id
                ],
                [
                    'Name',
                    $this->truncate($customer->name, 40),
                    $customer->decrypted_name ?? 'NULL'
                ],
                [
                    'Email',
                    $this->truncate($customer->email, 40),
                    $customer->decrypted_email ?? 'NULL'
                ],
                [
                    'Phone',
                    $this->truncate($customer->phone, 40),
                    $customer->decrypted_phone ?? 'NULL'
                ],
                [
                    'City',
                    $this->truncate($customer->city, 40),
                    $customer->decrypted_city ?? 'NULL'
                ],
                [
                    'Address1',
                    $this->truncate($customer->address1, 40),
                    $customer->decrypted_address1 ?? 'NULL'
                ],
                [
                    'Package',
                    $customer->packagecode,
                    optional($customer->package)->packagename ?? 'N/A'
                ],
            ]
        );

        $this->info('Entry Code: ' . $customer->entrycode);
        $this->info('Active: ' . ($customer->active ? 'Yes' : 'No'));
        $this->info('Verified: ' . ($customer->isverified == '1' ? 'Yes' : 'No'));
    }

    /**
     * Truncate string for display
     */
    private function truncate($string, $length = 40)
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        return substr($string, 0, $length) . '...';
    }
}
