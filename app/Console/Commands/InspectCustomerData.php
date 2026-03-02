<?php

namespace App\Console\Commands;

use App\Models\EncCustomerMaster;
use Illuminate\Console\Command;

class InspectCustomerData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:inspect-data {customer_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect raw encrypted data for a specific customer';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $customerId = $this->argument('customer_id');
        $customer = EncCustomerMaster::find($customerId);

        if (!$customer) {
            $this->error("Customer with ID {$customerId} not found");
            return 1;
        }

        $this->info("=== CUSTOMER ID: {$customer->id} ===");
        $this->line('');

        $this->analyzeField('NAME', $customer->name, $customer->decrypted_name);
        $this->line('');
        
        $this->analyzeField('EMAIL', $customer->email, $customer->decrypted_email);
        $this->line('');
        
        $this->analyzeField('PHONE', $customer->phone, $customer->decrypted_phone);
        $this->line('');
        
        $this->analyzeField('CITY', $customer->city, $customer->decrypted_city);
        $this->line('');
        
        $this->analyzeField('ADDRESS1', $customer->address1, $customer->decrypted_address1);
        $this->line('');

        $this->info('Other Fields:');
        $this->line("Entry Code: {$customer->entrycode}");
        $this->line("Package Code: {$customer->packagecode}");
        $this->line("Active: " . ($customer->active ? 'Yes' : 'No'));
        $this->line("Verified: " . ($customer->isverified == '1' ? 'Yes' : 'No'));

        return 0;
    }

    /**
     * Analyze a single field
     */
    private function analyzeField(string $fieldName, ?string $encrypted, ?string $decrypted)
    {
        $this->info("--- {$fieldName} ---");
        
        if (empty($encrypted)) {
            $this->line("Encrypted: [NULL/EMPTY]");
            $this->line("Decrypted: [NULL]");
            $this->line("Status: ⚠️  Empty field");
            return;
        }

        // Display encrypted value
        $this->line("Encrypted (HEX): " . $this->truncate($encrypted, 80));
        $this->line("Length: " . strlen($encrypted) . " characters");

        // Validate hex format
        $isValidHex = ctype_xdigit($encrypted);
        $isEvenLength = strlen($encrypted) % 2 === 0;

        if (!$isValidHex) {
            $this->error("❌ NOT VALID HEX - Contains non-hexadecimal characters");
            $this->line("First 100 chars: " . substr($encrypted, 0, 100));
            return;
        }

        if (!$isEvenLength) {
            $this->error("❌ ODD LENGTH - Hex strings must have even number of characters");
            return;
        }

        $this->line("Hex Format: ✅ Valid");

        // Try decryption
        if ($decrypted === null) {
            $this->error("Decrypted: ❌ DECRYPTION FAILED");
            
            // Try to diagnose why
            $binary = @hex2bin($encrypted);
            if ($binary === false) {
                $this->error("Reason: hex2bin() conversion failed");
            } else {
                $this->error("Reason: openssl_decrypt() failed (wrong key or corrupted data)");
            }
        } else {
            $this->info("Decrypted: ✅ \"{$decrypted}\"");
            $this->line("Decrypted Length: " . strlen($decrypted) . " characters");
        }
    }

    /**
     * Truncate string for display
     */
    private function truncate(string $string, int $length = 80): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        return substr($string, 0, $length) . '...';
    }
}
