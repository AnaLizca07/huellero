<?php

namespace App\Services;

use App\Models\AttendanceLog;
use Carbon\Carbon;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\Log;

class ZKTecoService
{
    private $zk;

    public function __construct()
    {
        $this->zk = new ZKTeco(
            config('zkteco.devices.default.ip'),
            config('zkteco.devices.default.port'),
            config('zkteco.devices.default.comm_key')
        );
    }

    public function connect()
    {
        try {
            ini_set('default_socket_timeout', 60);
            return $this->zk->connect();
        } catch (\Exception $e) {
            Log::error('Error de conexión: ' . $e->getMessage());
            return false;
        }
    }

    public function getAttendanceLogs()
    {
        try {
            if (!$this->connect()) {
                throw new \Exception('No se pudo conectar al dispositivo');
            }

            // Obtenemos la versión del dispositivo para verificar la conexión
            $version = $this->zk->version();
            Log::info('Versión del dispositivo: ' . $version);

            // Obtenemos los datos de asistencia
            $attendances = $this->zk->getAttendance();
            Log::info('Datos recibidos: ' . json_encode($attendances));

            if (!empty($attendances)) {
                foreach ($attendances as $attendance) {
                    AttendanceLog::create([
                        'user_id' => $attendance['id'],
                        'name' => $attendance['name'] ?? 'Unknown',
                        'timestamp' => Carbon::parse($attendance['timestamp']),
                        'type' => $this->determineLogType($attendance['timestamp'])
                    ]);
                }
            }

            $this->zk->disconnect();
            return $attendances;

        } catch (\Exception $e) {
            Log::error('Error al obtener registros: ' . $e->getMessage());
            if ($this->zk) {
                $this->zk->disconnect();
            }
            throw $e;
        }
    }

    private function determineLogType($timestamp)
    {
        $hour = Carbon::parse($timestamp)->hour;
        return $hour < 12 ? 'check_in' : 'check_out';
    }



    // Método para obtener usuarios
    public function getUsers()
    {
        try {
            if (!$this->connect()) {
                throw new \Exception('No se pudo conectar al dispositivo');
            }

            $users = $this->zk->getUser();
            Log::info('Usuarios obtenidos: ' . json_encode($users));

            $this->zk->disconnect();
            return $users;

        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios: ' . $e->getMessage());
            if ($this->zk) {
                $this->zk->disconnect();
            }
            throw $e;
        }
    }

    
}