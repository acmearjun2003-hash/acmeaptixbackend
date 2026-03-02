<?php

namespace App\Console\Commands;

use App\Models\EncCustomerMaster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseEncryptedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:diagnose-encryption {--limit=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose and identify problematic encrypted data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Diagnosing encrypted customer data (limit: {$limit})...");
        $this->line('');

        // Get sample customers
        $customers = EncCustomerMaster::limit($limit)->get();

        if ($customers->isEmpty()) {
            $this->error('No customers found in database');
            return 1;
        }

        $this->info("Found {$customers->count()} customers to analyze");
        $this->line('');

        $issues = [
            'invalid_name' => [],
            'invalid_email' => [],
            'invalid_phone' => [],
            'invalid_city' => [],
            'decryption_failed' => [],
        ];

        $successCount = 0;

        $this->info('Analyzing...');
        $bar = $this->output->createProgressBar($customers->count());

        foreach ($customers as $customer) {
            $hasIssue = false;

            // Check name
            if (!$this->isValidHex($customer->name)) {
                $issues['invalid_name'][] = $customer->id;
                $hasIssue = true;
            }

            // Check email
            if (!$this->isValidHex($customer->email)) {
                $issues['invalid_email'][] = $customer->id;
                $hasIssue = true;
            }

            // Check phone
            if (!$this->isValidHex($customer->phone)) {
                $issues['invalid_phone'][] = $customer->id;
                $hasIssue = true;
            }

            // Check city
            if (!$this->isValidHex($customer->city)) {
                $issues['invalid_city'][] = $customer->id;
                $hasIssue = true;
            }

            // Try decryption
            try {
                $decryptedName = $customer->decrypted_name;
                $decryptedEmail = $customer->decrypted_email;
                $decryptedPhone = $customer->decrypted_phone;
                $decryptedCity = $customer->decrypted_city;
                
                if (!$hasIssue) {
                    $successCount++;
                }
            } catch (\Exception $e) {
                $issues['decryption_failed'][] = [
                    'id' => $customer->id,
                    'error' => $e->getMessage()
                ];
                $hasIssue = true;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->line('');

        // Display results
        $this->info('=== DIAGNOSIS RESULTS ===');
        $this->line('');

        $this->info("✅ Successfully processed: {$successCount}/{$customers->count()}");
        $this->line('');

        $totalIssues = 0;

        if (count($issues['invalid_name']) > 0) {
            $count = count($issues['invalid_name']);
            $totalIssues += $count;
            $this->warn("❌ Invalid NAME hex format: {$count} records");
            $this->line('   Customer IDs: ' . implode(', ', array_slice($issues['invalid_name'], 0, 10)));
            if ($count > 10) {
                $this->line("   ... and " . ($count - 10) . " more");
            }
            $this->line('');
        }

        if (count($issues['invalid_email']) > 0) {
            $count = count($issues['invalid_email']);
            $totalIssues += $count;
            $this->warn("❌ Invalid EMAIL hex format: {$count} records");
            $this->line('   Customer IDs: ' . implode(', ', array_slice($issues['invalid_email'], 0, 10)));
            if ($count > 10) {
                $this->line("   ... and " . ($count - 10) . " more");
            }
            $this->line('');
        }

        if (count($issues['invalid_phone']) > 0) {
            $count = count($issues['invalid_phone']);
            $totalIssues += $count;
            $this->warn("❌ Invalid PHONE hex format: {$count} records");
            $this->line('   Customer IDs: ' . implode(', ', array_slice($issues['invalid_phone'], 0, 10)));
            if ($count > 10) {
                $this->line("   ... and " . ($count - 10) . " more");
            }
            $this->line('');
        }

        if (count($issues['invalid_city']) > 0) {
            $count = count($issues['invalid_city']);
            $totalIssues += $count;
            $this->warn("❌ Invalid CITY hex format: {$count} records");
            $this->line('   Customer IDs: ' . implode(', ', array_slice($issues['invalid_city'], 0, 10)));
            if ($count > 10) {
                $this->line("   ... and " . ($count - 10) . " more");
            }
            $this->line('');
        }

        if (count($issues['decryption_failed']) > 0) {
            $count = count($issues['decryption_failed']);
            $totalIssues += $count;
            $this->error("❌ Decryption FAILED: {$count} records");
            foreach (array_slice($issues['decryption_failed'], 0, 5) as $issue) {
                $this->line("   ID {$issue['id']}: {$issue['error']}");
            }
            if ($count > 5) {
                $this->line("   ... and " . ($count - 5) . " more");
            }
            $this->line('');
        }

        if ($totalIssues === 0) {
            $this->info('🎉 No issues found! All data is properly encrypted.');
        } else {
            $this->line('');
            $this->warn("Total issues found: {$totalIssues}");
            $this->line('');
            $this->info('Recommendations:');
            $this->line('1. Check the encryption key is correct: "AcmeInfovision@1994#"');
            $this->line('2. Verify data in database is in uppercase HEX format');
            $this->line('3. Run: php artisan customer:inspect-data <customer_id> to see raw data');
        }

        $this->line('');

        return 0;
    }

    /**
     * Check if string is valid hexadecimal
     */
    private function isValidHex(?string $value): bool
    {
        if (empty($value)) {
            return true; // NULL/empty is okay
        }

        $value = trim($value);

        // Check if it contains only hex characters
        if (!ctype_xdigit($value)) {
            return false;
        }

        // Check if length is even
        if (strlen($value) % 2 !== 0) {
            return false;
        }

        return true;
    }
}
