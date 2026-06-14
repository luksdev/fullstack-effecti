<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Idempotent so it can run on every container boot without duplicating data.
     */
    public function run(): void
    {
        // Demo account used to pre-fill the login screen.
        User::firstOrCreate(
            ['email' => 'admin@effecti.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        if (Customer::query()->exists()) {
            return;
        }

        $customers = Customer::factory()->count(5)->create();
        $services = Service::factory()->count(5)->create();

        // A sample contract whose items trigger both discount rules
        // (subtotal R$ 1.300,00 -> 10% progressive; 5 items -> 5% quantity).
        $contract = Contract::factory()->create([
            'customer_id' => $customers->first()->id,
        ]);

        $contract->contractItems()->createMany([
            ['service_id' => $services[0]->id, 'quantity' => 3, 'unit_price' => 30000],
            ['service_id' => $services[1]->id, 'quantity' => 2, 'unit_price' => 20000],
        ]);
    }
}
