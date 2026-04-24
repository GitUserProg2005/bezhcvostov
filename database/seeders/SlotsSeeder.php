<?php

namespace Database\Seeders;

use App\Models\Slot;
use Illuminate\Database\Seeder;

class SlotsSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['type' => 'computer', 'x' => 0.40, 'y' => 0.76, 'scale' => 0.60],
            ['type' => 'bed', 'x' => 0.35, 'y' => 0.82, 'scale' => 0.60],
            ['type' => 'larder', 'x' => 0.18, 'y' => 0.58, 'scale' => 0.60],
            ['type' => 'accessory', 'x' => 0.25, 'y' => 0.35, 'scale' => 0.60],
            ['type' => 'transport', 'x' => 0.62, 'y' => 0.33, 'scale' => 2.10],
        ];

        foreach ($slots as $slotData) {
            Slot::query()->updateOrCreate(
                ['type' => $slotData['type']],
                $slotData,
            );
        }
    }
}
