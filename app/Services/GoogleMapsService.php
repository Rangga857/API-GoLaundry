<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.key');
    }


    public function geocodeAddress(string $address): ?array
    {
        try {
            $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
                'address' => $address,
                'key' => $this->apiKey,
            ]);

            $data = $response->json();

            // Log respons dari Google Maps Geocoding API untuk debugging
            Log::info('Google Maps Geocoding API response:', ['response' => $data]);

            // Memeriksa status dan memastikan ada hasil yang valid
            if ($data['status'] === 'OK' && !empty($data['results']) && isset($data['results'][0]['geometry']['location'])) {
                $latitude = $data['results'][0]['geometry']['location']['lat'];
                $longitude = $data['results'][0]['geometry']['location']['lng'];

                return ['latitude' => $latitude, 'longitude' => $longitude];
            }

            // Log error jika koordinat tidak dapat diperoleh
            Log::error('Error getting coordinates from Google Maps Geocoding API:', ['response' => $data, 'address' => $address]);
            return null;
        } catch (\Exception $e) {
            // Menangkap exception jika ada masalah selama panggilan API
            Log::error('Exception during Google Maps Geocoding API call:', ['error' => $e->getMessage(), 'address' => $address]);
            return null;
        }
    }

    public function getDistanceMatrix(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng
    ): ?array
    {
        try {
            $origins = "{$originLat},{$originLng}";
            $destinations = "{$destinationLat},{$destinationLng}";

            $response = Http::get("https://maps.googleapis.com/maps/api/distancematrix/json", [
                'origins' => $origins,
                'destinations' => $destinations,
                'key' => $this->apiKey,
                'mode' => 'driving', 
                'units' => 'metric', 
            ]);

            $data = $response->json();

            // Log respons dari Google Maps Distance Matrix API untuk debugging
            Log::info('Google Maps Distance Matrix API response:', ['response' => $data]);

            if ($data['status'] === 'OK' && !empty($data['rows'][0]['elements'][0]['distance'])) {
                $element = $data['rows'][0]['elements'][0];
                if ($element['status'] === 'OK') {
                    return [
                        'distance_meters' => $element['distance']['value'],
                        'duration_seconds' => $element['duration']['value'], 
                    ];
                }
            }
            // Log error jika jarak tidak dapat diperoleh
            Log::error('Error getting distance from Google Maps Distance Matrix API:', ['response' => $data, 'origins' => $origins, 'destinations' => $destinations]);
            return null;
        } catch (\Exception $e) {
            // Menangkap exception jika ada masalah selama panggilan API
            Log::error('Exception during Google Maps Distance Matrix API call:', ['error' => $e->getMessage(), 'origins' => $origins, 'destinations' => $destinations]);
            return null;
        }
    }
}
