<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;

class GeolocationController extends BaseController
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'street' => 'required|string|min:2'
        ]);
        $certPath = storage_path('certs/cacert.pem');
        $response = Http::withOptions([
            'verify' => $certPath
        ])->withHeaders([
            'User-Agent' => 'Laravel-Geolocation/1.0'
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $data['street'],
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1
        ]);
        if (!$response->ok()) {
            return response()->json(['message' => 'Erro ao consultar serviço de geolocalização'], 502);
        }
        $body = $response->json();
        if (empty($body)) {
            return response()->json(['message' => 'Endereço não encontrado'], 404);
        }
        $first = $body[0];
        return response()->json([
            'query' => $data['street'],
            'latitude' => (float) $first['lat'],
            'longitude' => (float) $first['lon'],
            'display_name' => $first['display_name'] ?? null
        ]);
    }
}
