<?php

namespace App\Http\Controllers;

use App\Services\ZKTecoService;
use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class ControladorTest extends Controller
{
    private $zktecoService;

    public function __construct(ZKTecoService $zktecoService)
    {
        $this->zktecoService = $zktecoService;
    }

    public function connect(){
        try {
            $connected = $this->zktecoService->connect();
         
            if (!$connected) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo conectar con el dispositivo'
                ], 500);
            }
            
            //$this->zktecoService->disconnect();
            
            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa'
            ]);

        } catch (Exception $e) {
            Log::error('Error en getAttendance: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function getAttendance()
    {
        try {
            // No necesitas conectar aquí porque getUserAttendance() ya maneja la conexión
            $result = $this->zktecoService->getUserAttendance();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    
}