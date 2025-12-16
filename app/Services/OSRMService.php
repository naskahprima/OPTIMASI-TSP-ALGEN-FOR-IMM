<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OSRMService
{
    protected $serverUrl;

    public function __construct()
    {
        // Ambil URL dari .env, atau pakai default
        $this->serverUrl = env('OSRM_SERVER_URL', 'https://router.project-osrm.org');
    }

    /**
     * Hitung jarak antara 2 koordinat menggunakan OSRM
     * 
     * @param float $originLat Latitude titik asal
     * @param float $originLng Longitude titik asal
     * @param float $destLat Latitude titik tujuan
     * @param float $destLng Longitude titik tujuan
     * @return array ['distance' => jarak dalam meter, 'duration' => waktu dalam detik]
     * @throws Exception Jika API call gagal
     */
    public function getDistance($originLat, $originLng, $destLat, $destLng)
    {
        try {
            // Format: lng,lat (OSRM pakai format terbalik dari Google Maps!)
            $coordinates = "{$originLng},{$originLat};{$destLng},{$destLat}";
            
            // Build URL
            $url = "{$this->serverUrl}/route/v1/driving/{$coordinates}";
            
            // Query parameters
            $params = [
                'overview' => 'false', // Kita cuma butuh jarak, gak butuh full route
                'geometries' => 'geojson',
            ];
            
            // Call API
            $response = Http::timeout(10)->get($url, $params);
            
            // Check response status
            if (!$response->successful()) {
                throw new Exception("OSRM API error: HTTP {$response->status()}");
            }
            
            $data = $response->json();
            
            // Validasi response
            if (!isset($data['code']) || $data['code'] !== 'Ok') {
                $errorMsg = $data['message'] ?? 'Unknown error';
                throw new Exception("OSRM routing failed: {$errorMsg}");
            }
            
            // Extract distance & duration
            if (!isset($data['routes'][0]['distance']) || !isset($data['routes'][0]['duration'])) {
                throw new Exception("Invalid OSRM response format");
            }
            
            $distance = $data['routes'][0]['distance']; // dalam meter
            $duration = $data['routes'][0]['duration']; // dalam detik
            
            // Log untuk debugging (optional, bisa di-comment kalau sudah production)
            Log::info("OSRM Distance calculated", [
                'from' => "{$originLat},{$originLng}",
                'to' => "{$destLat},{$destLng}",
                'distance_m' => $distance,
                'duration_s' => $duration
            ]);
            
            return [
                'distance' => $distance,
                'duration' => $duration,
            ];
            
        } catch (Exception $e) {
            // Log error
            Log::error("OSRM Service Error: " . $e->getMessage(), [
                'origin' => "{$originLat},{$originLng}",
                'destination' => "{$destLat},{$destLng}",
            ]);
            
            // Re-throw exception agar bisa di-handle oleh controller
            throw $e;
        }
    }

    /**
     * Get full route dengan geometri (untuk visualisasi di map)
     * 
     * @param array $coordinates Array of [lat, lng] pairs
     * @return array Route data dengan geometry
     */
    public function getRoute($coordinates)
    {
        try {
            // Convert coordinates ke format OSRM (lng,lat)
            $coordsString = collect($coordinates)
                ->map(fn($coord) => "{$coord['lng']},{$coord['lat']}")
                ->implode(';');
            
            $url = "{$this->serverUrl}/route/v1/driving/{$coordsString}";
            
            $params = [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'true', // Include turn-by-turn directions
            ];
            
            $response = Http::timeout(15)->get($url, $params);
            
            if (!$response->successful()) {
                throw new Exception("OSRM API error: HTTP {$response->status()}");
            }
            
            $data = $response->json();
            
            if (!isset($data['code']) || $data['code'] !== 'Ok') {
                throw new Exception("OSRM routing failed");
            }
            
            return $data['routes'][0];
            
        } catch (Exception $e) {
            Log::error("OSRM Route Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check apakah OSRM server available
     * 
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $response = Http::timeout(5)->get($this->serverUrl);
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }
}