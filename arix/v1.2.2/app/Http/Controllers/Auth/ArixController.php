<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Support\Facades\Http;

class ArixController extends AbstractLoginController
{
    public function index(): object
    {

        $endpoint = 'https://api.arix.gg/resource/arix-pterodactyl/verify';
    
        $response = Http::asForm()->post($endpoint, [
            'license' => 'ARIX-CHECK',
        ]);
    
        $responseData = $response->json();
    
        if (!$responseData['success']) {
            return response()->json([
                'status' => 'Not available'
            ]);
        }

        return response()->json([
            'NONCE' => 'c83c0fcf3213436cca43069cc9b456e8',
            'ID' => '176134',
            'USERNAME' => 'ShayPunter',
            'TIMESTAMP' => '1744745760'
        ]);
    }
}