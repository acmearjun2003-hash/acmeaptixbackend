<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\EncCustomerMaster;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EncryptedCustomerSearchTest extends TestCase
{
    /**
     * Test encryption and decryption
     */
    public function test_encryption_decryption()
    {
        $customer = EncCustomerMaster::first();
        
        if (!$customer) {
            $this->markTestSkipped('No customers in database');
        }

        // Test that decryption accessors work
        $this->assertNotNull($customer->decrypted_name);
        $this->assertNotEmpty($customer->decrypted_name);
        
        // Verify encrypted field is still HEX
        $this->assertMatchesRegularExpression('/^[A-F0-9]+$/', $customer->name);
    }

    /**
     * Test customer search API with packagecode filter
     */
    public function test_search_by_packagecode()
    {
        $response = $this->getJson('/api/erp-customers?packagecode=3');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'email',
                             'phone',
                             'city',
                             'packagecode',
                             'package_display_name',
                             'package',
                         ]
                     ],
                     'pagination'
                 ]);
    }

    /**
     * Test customer search API with phone filter
     */
    public function test_search_by_phone()
    {
        $response = $this->getJson('/api/erp-customers?phone=9876543210');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'pagination'
                 ]);
    }

    /**
     * Test customer search API with text search
     */
    public function test_text_search()
    {
        $response = $this->getJson('/api/erp-customers?search=test');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'pagination'
                 ]);

        // Check if decrypted data is returned
        $data = $response->json('data');
        if (count($data) > 0) {
            $this->assertArrayHasKey('name', $data[0]);
            $this->assertArrayHasKey('email', $data[0]);
            $this->assertArrayHasKey('phone', $data[0]);
            
            // Verify data is decrypted (not HEX)
            $this->assertDoesNotMatchRegularExpression('/^[A-F0-9]+$/', $data[0]['name']);
        }
    }

    /**
     * Test customer search API with pagination
     */
    public function test_pagination()
    {
        $response = $this->getJson('/api/erp-customers?per_page=5&page=1');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        $this->assertEquals(5, $pagination['per_page']);
        $this->assertEquals(1, $pagination['current_page']);
    }

    /**
     * Test get all customers endpoint
     */
    public function test_get_all_customers()
    {
        $response = $this->getJson('/api/erp-customers/all?active_only=true');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'total'
                 ]);
    }

    /**
     * Test combined filters
     */
    public function test_combined_filters()
    {
        $response = $this->getJson('/api/erp-customers?packagecode=3&is_verified=true&per_page=10');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Verify filters are applied
        foreach ($data as $customer) {
            $this->assertEquals(3, $customer['packagecode']);
        }
    }

    /**
     * Test invalid parameters
     */
    public function test_validation_errors()
    {
        $response = $this->getJson('/api/erp-customers?per_page=500');

        $response->assertStatus(422); // Validation error
    }
}
