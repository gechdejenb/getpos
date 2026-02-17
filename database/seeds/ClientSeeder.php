<?php


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       	// Insert some stuff
        DB::table('clients')->insert(
            array(
                'id'     => 1,
                'name'   => 'new-customer',
                'code' => 1,
                'email' => 'new-customer@example.com',
                'country' => 'Ethiopia',
                'city' => 'Addis Ababa',
                'phone' => '0902527607',
                'adresse' => 'Bole Road',
                'tax_number' => NULL,
            )
            
        );
    }
}
