<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Slot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ItemsSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            'old' => 0,
            'middle' => 50,
            'new' => 100,
        ];

        $types = ['computer', 'bed', 'larder', 'transport'];
        $typePictureMap = [
            'computer' => 'computer',
            'bed' => 'bed',
            'larder' => 'larder',
            'transport' => 'bike',
        ];

        foreach ($types as $type) {
            $slot = Slot::query()->where('type', $type)->first();

            if (! $slot) {
                continue;
            }

            foreach ($prices as $quality => $price) {
                $title = ucfirst($quality).' '.ucfirst($type);
                $pictureSuffix = $typePictureMap[$type] ?? $type;
                $picture = "img/game/store/{$quality}_{$pictureSuffix}.png";
                $publicPath = public_path($picture);

                if (! File::exists($publicPath)) {
                    continue;
                }

                Item::query()->updateOrCreate(
                    ['slot_id' => $slot->id, 'title' => $title],
                    [
                        'description' => "Tier {$quality} for {$type}",
                        'price' => $price,
                        'picture' => $picture,
                    ],
                );
            }
        }

        $accessorySlot = Slot::query()->where('type', 'accessory')->first();

        if ($accessorySlot) {
            $accessoryPicture = 'img/game/store/kaktus.png';

            if (! File::exists(public_path($accessoryPicture))) {
                return;
            }

            Item::query()->updateOrCreate(
                ['slot_id' => $accessorySlot->id, 'title' => 'Middle Accessory'],
                [
                    'description' => 'Decorative accessory item',
                    'price' => $prices['middle'],
                    'picture' => $accessoryPicture,
                ],
            );
        }
    }
}
