<?php

namespace App\Http\Controllers;

use App\Services\ZKTecoService;
use App\Models\AttendanceLog;
use Illuminate\Routing\Controller;

class AttendanceController extends Controller
{
    private $zkTecoService;

    public function __construct(ZKTecoService $zkTecoService)
    {
        $this->zkTecoService = $zkTecoService;
    }

    public function syncAttendance()
    {
        try {
            $logs = $this->zkTecoService->getAttendanceLogs();
            return response()->json([
                'success' => true,
                'message' => 'Registros sincronizados correctamente',
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUsers()
    {
        try {
            $users = $this->zkTecoService->getUsers();
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLogs()
    {
        $logs = AttendanceLog::orderBy('timestamp', 'desc')->paginate(15);
        return response()->json($logs);
    }


    
}