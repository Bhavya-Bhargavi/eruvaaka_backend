<?php

namespace Database\Seeders;

use App\Models\AgriculturalJob;
use Illuminate\Database\Seeder;

class JobDemoSeeder extends Seeder
{
    public function run(): void
    {
        AgriculturalJob::updateOrCreate(['title' => 'Sample Farm Worker'], [
            'description' => 'Temporary sample listing for testing the jobs API. Assist with planting, irrigation, and harvesting.',
            'location' => 'Guntur, Andhra Pradesh',
            'salary' => 'Sample: Rs. 15,000 per month',
            'contact_person' => 'Sample Contact 1',
            'contact_phone' => '0000000000',
            'status' => 'active',
        ]);

        AgriculturalJob::updateOrCreate(['title' => 'Sample Tractor Operator'], [
            'description' => 'Temporary sample listing for testing the jobs API. Operate and maintain farm machinery.',
            'location' => 'Vijayawada, Andhra Pradesh',
            'salary' => 'Sample: Rs. 20,000 per month',
            'contact_person' => 'Sample Contact 2',
            'contact_phone' => '0000000000',
            'status' => 'active',
        ]);
    }
}
