<?php

use Illuminate\Database\Seeder;

class DummyNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\DummyClass::class)->create();
    }
}
