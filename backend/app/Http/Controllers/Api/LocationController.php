<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Locality;
use App\Models\Region;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
           'query' => 'required|string|min:2',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . config('services.dadata.apikey'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post(
            'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address',
            [
                'query' => $request->input('query'),
                'count' => 10,
                'from_bound' => ["value" => "city"],
                'to_bound' => ["value" => "settlement"],
            ]
        );
        if (!$response->successful()) {

            return response()->json([
                'items' => [],
            ]);
        }

        $suggestions = collect(
            $response->json('suggestions', [])
        );

        $items = $suggestions
            ->filter(function ($item) {

                return $item['data']['settlement_type']!=="р-н" && $item['data']['settlement_type']!=="тер";

            })
            ->map(function ($item) {

                return $item;

            })
            ->values();

        return response()->json([
            'items' => $items,
        ]);

    }

    public function addCity(Request $request): JsonResponse
    {
        $received_data = $request->all();
        $data = $received_data['data'];

        $country = Country::firstOrCreate(
            [
                'name' => $data['country'],
                'code' => $data['country_iso_code']
            ]
        );

        $region = Region::firstOrCreate(
            ['fias_id' => $data['region_fias_id']], // условие поиска
            [                                       // данные для создания (если не найдено)
                'name'        => $data['region'],
                'type'        => $data['region_type_full'],
                'country_id'  => $country->id,      // передаём ID, а не модель
            ]
        );

        $locality = Locality::firstOrCreate(
            ['fias_id' => $data['fias_id']],
            [
                'name'        => $data['settlement']??$data['city'],
                'country_id'  => $country->id,
                'region_id'  => $region->id,
                'type'  => $data['settlement_type']??$data['city_type'],
                'full_name'  => $received_data['value'],
                'slug'  => Str::slug($data['settlement']??$data['city']),
                'fias_id'  => $data['settlement_fias_id']??$data['city_fias_id'],
                'kladr_id'  => $data['settlement_kladr_id']??$data['city_kladr_id'],
                'latitude'  => $data['geo_lat'],
                'longitude'  => $data['geo_lon'],
                'population'  => 0,
            ]
        );

        return response()->json([
            'request' => $data,
            'country' => $country,
        ]);
    }
}
