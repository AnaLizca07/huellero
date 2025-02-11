

namespace App\Http\Controllers;

use App\Services\ZKTecoService;
use App\Models\AttendanceLog;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

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
            set_time_limit(300);
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

    public function connect()
    {
        try {
            $status = $this->zkTecoService->connect();
            dd($status);
            return response()->json([
                'success' => $status,
                'message' => $status ? 'Conexión exitosa....' : 'No se pudo conectar al dispositivo'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAttendanceReport(Request $request)
    {
        $query = AttendanceLog::query()
            ->when($request->start_date, function($q) use ($request) {
                return $q->whereDate('timestamp', '>=', $request->start_date);
            })
            ->when($request->end_date, function($q) use ($request) {
                return $q->whereDate('timestamp', '<=', $request->end_date);
            })
            ->when($request->user_id, function($q) use ($request) {
                return $q->where('user_id', $request->user_id);
            })
            ->orderBy('timestamp', 'desc');

        return response()->json($query->paginate(15));
    }

    
}