<?php

namespace Database\Seeders;

use App\Models\FleetVehicle;
use App\Models\Service;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

/**
 * The fleet (katalog_armada block, Linehaul card photo) on its own, so a live
 * site can refresh just the trucks without touching anything else the client
 * edited: php artisan db:seed --class=FleetVehicleSeeder --force
 *
 * Safe to run again: trucks are matched by model (updateOrCreate) and a photo
 * is only attached when the truck has none yet.
 */
class FleetVehicleSeeder extends Seeder
{
    use SeedsImages;

    /** Replaced by the trucks below; only removed while still unedited sample data. */
    private const RETIRED = ['FE 71 Long', 'FE 74 Long'];

    public function run(): void
    {
        // DUMMY specifications (payloads are typical for each class, to be confirmed
        // by the client); the side views are the client's artwork (not edited).
        // Two categories: refrigerated box trucks (reefer) and the dry Tronton
        // chassis & container (up to 40 tons, logistics).
        // model, image, type, [tempMin, tempMax, payloadKg, length, width, height], [en, id]
        $trucks = [
            ['L300', 'fleet-l300-side', 'reefer', [-20, 5, 1000, null, null, null],
                ['Compact refrigerated box for tight city streets and small, frequent drops.', 'Boks berpendingin ringkas untuk jalan sempit dalam kota dan pengiriman kecil yang sering.']],
            ['CDE', 'fleet-cde-side', 'reefer', [-20, 5, 3500, null, null, null],
                ['Medium refrigerated box truck for city runs with mixed chilled and frozen loads.', 'Truk boks berpendingin ukuran sedang untuk rute dalam kota dengan muatan chiller dan frozen campuran.']],
            ['CDD', 'fleet-cdd-side', 'reefer', [-20, 5, 8000, null, null, null],
                ['Six-wheel refrigerated box truck for larger volumes between cities.', 'Truk boks berpendingin enam roda untuk volume lebih besar antarkota.']],
            ['Fuso Fighter', 'fleet-fuso-fighter-side', 'reefer', [-20, 5, 12000, null, null, null],
                ['Heavy refrigerated box truck for long-haul loads that must stay cold all the way.', 'Truk boks berpendingin kelas berat untuk muatan jarak jauh yang harus tetap dingin sepanjang jalan.']],
            ['Tronton Container', 'fleet-tronton-side', 'dry', [null, null, 40000, null, null, null],
                ['Tronton chassis and container for dry, bulk logistics: up to 40 tons per trip.', 'Tronton chassis dan kontainer untuk logistik kering dalam jumlah besar: hingga 40 ton per perjalanan.']],
        ];

        foreach ($trucks as $index => [$model, $image, $type, [$tempMin, $tempMax, $capacity, $length, $boxWidth, $boxHeight], $description]) {
            $vehicle = FleetVehicle::query()->updateOrCreate(['model' => $model], [
                'type' => $type,
                'description' => ['en' => $description[0], 'id' => $description[1]],
                'temp_min' => $tempMin,
                'temp_max' => $tempMax,
                'capacity_kg' => $capacity,
                'box_length' => $length,
                'box_width' => $boxWidth,
                'box_height' => $boxHeight,
                'focus_x' => 50,
                'focus_y' => 50,
                'zoom' => 100,
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_sample' => true,
            ]);
            $vehicle->markTranslationsReviewed('id')->save();
            $this->attachServiceImage($vehicle, 'side', $image, 1774, 998);
        }

        $this->retireOldTrucks();
    }

    /**
     * The earlier sample trucks (FE 71 / FE 74) would otherwise stay next to the new
     * ones (the block lists every active truck). Only rows still marked as sample
     * are removed; the Linehaul card is pointed at the Fuso Fighter instead.
     */
    private function retireOldTrucks(): void
    {
        $old = FleetVehicle::query()->whereIn('model', self::RETIRED)->where('is_sample', true)->get();

        if ($old->isEmpty()) {
            return;
        }

        $fighter = FleetVehicle::query()->where('model', 'Fuso Fighter')->value('id');

        Service::query()->whereIn('card_fleet_vehicle_id', $old->modelKeys())
            ->update(['card_fleet_vehicle_id' => $fighter]);

        $old->each->delete();
    }
}
