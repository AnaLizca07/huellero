<?php

namespace App\Services;

use Exception;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\Log;

class ZKTecoService
{
    private $zkteco;
    private $ip = '192.168.0.30';
    private $port = 4370;

    public function __construct()
    {
        if (empty($this->ip) || empty($this->port)) {
            throw new Exception('ZKTeco IP or Port not configured properly');
        }

        Log::info('ZKTeco Config:', [
            'ip' => $this->ip,
            'port' => $this->port
        ]);

        $this->zkteco = new ZKTeco($this->ip, $this->port);
    }

    public function connect()
    {
        try {
            if (!$this->zkteco) {
                throw new Exception('ZKTeco instance not initialized');
            }

            if ($this->zkteco->connect()) {
                Log::info('Successfully connected to ZKTeco device');
                return true;
            }
            
            Log::error('Failed to connect to ZKTeco device');
            return false;
        } catch (\Exception $e) {
            Log::error('Error conectando con ZKTeco: ' . $e->getMessage());
            return false;
        }
    }

    public function disconnect()
    {
        try {
            if (!$this->zkteco) {
                throw new Exception('ZKTeco instance not initialized');
            }

            if ($this->zkteco->disconnect()) {
                Log::info('Successfully disconnected from ZKTeco device');
                return true;
            }
            
            Log::error('Failed to disconnect from ZKTeco device');
            return false;
        } catch (\Exception $e) {
            Log::error('Error desconectando ZKTeco: ' . $e->getMessage());
            return false;
        }
    }

    public function getUserAttendance()
    {
        try {
            if (!$this->connect()) {
                throw new Exception('No se pudo conectar con el dispositivo');
            }
    
            // Primero obtener usuarios
            $users = $this->zkteco->getUser();
            Log::info('Usuarios encontrados:', ['users' => $users]);

           /* // Luego obtener asistencia
            $attendanceData = $this->zkteco->getAttendance();
            Log::info('Asistencia encontrada:', ['attendance' => $attendanceData]);


    
            // Organizar datos por usuario y fecha
            $userAttendance = [];
            
            foreach ($attendanceData as $record) {
                Log::info('Procesando registro:', ['record' => $record]);
                
                $date = date('Y-m-d', strtotime($record['timestamp']));
                $time = date('H:i:s', strtotime($record['timestamp']));
                $uid = $record['uid'];
                
                // Inicializar el array para este usuario si no existe
                if (!isset($userAttendance[$uid])) {
                    $userAttendance[$uid] = [
                        'uid' => $uid,
                        'nombres' => $record['id'],
                        'registros' => []
                    ];
                }
                
                // Agregar el registro
                $userAttendance[$uid]['registros'][] = [
                    'fecha' => $date,
                    'hora' => $time,
                    'tipo' => ($record['state'] == 1) ? 'Entrada' : 'Salida'
                ];
            }
    
            Log::info('Datos procesados:', ['userAttendance' => $userAttendance]);
    
            $this->disconnect();
    
            return [
                'success' => true,
                'data' => array_values($userAttendance)
            ];*/
    
        } catch (\Exception $e) {
            Log::error('Error obteniendo asistencia: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            try {
                $this->disconnect();
            } catch (\Exception $disconnectError) {
                Log::error('Error al desconectar: ' . $disconnectError->getMessage());
            }
    
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}