<?php

namespace App\Services;

use App\Services\ZKTecoService;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\Log;

class ZKTecoTest extends ZKTecoService
{
    private $zk;

    public function __construct()
    {
        try {
            parent::__construct();
            Log::info('Iniciando ZKTecoTest', [
                'ip' => config('zkteco.devices.default.ip'),
                'port' => config('zkteco.devices.default.port')
            ]);
            
            $this->zk = new ZKTeco(
                config('zkteco.devices.default.ip'),
                config('zkteco.devices.default.port'),
                config('zkteco.devices.default.comm_key')
            );
        } catch (\Exception $e) {
            Log::error('Error en constructor ZKTecoTest', [
                'message' => $e->getMessage()
            ]);
        }
    }

    public function testDevice()
    {
        try {
            Log::info('Iniciando prueba de dispositivo');

            $ip = config('zkteco.devices.default.ip');
            $port = config('zkteco.devices.default.port');

            // Test de ping básico primero
            exec("ping -n 1 " . escapeshellarg($ip), $pingOutput, $pingStatus);
            
            $basicTests = [
                'ip' => $ip,
                'port' => $port,
                'ping_successful' => ($pingStatus === 0),
                'ping_output' => implode("\n", array_map('utf8_encode', $pingOutput))
            ];

            // Si el ping falla, retornamos temprano
            if (!$basicTests['ping_successful']) {
                return [
                    'status' => false,
                    'message' => 'El dispositivo no responde al ping',
                    'tests' => $basicTests
                ];
            }

            // Prueba de socket UDP
            $socket = @fsockopen('udp://' . $ip, $port, $errno, $errstr, 1);
            $basicTests['socket_test'] = [
                'success' => ($socket !== false),
                'error' => $socket ? null : htmlspecialchars($errstr)
            ];

            if ($socket) {
                fclose($socket);
            }

            // Si no podemos establecer conexión UDP, retornamos
            if (!$basicTests['socket_test']['success']) {
                return [
                    'status' => false,
                    'message' => 'No se puede establecer conexión UDP',
                    'tests' => $basicTests
                ];
            }

            // Intentamos conectar con el dispositivo
            if (!$this->connect()) {
                return [
                    'status' => false,
                    'message' => 'No se pudo establecer conexión con el dispositivo',
                    'tests' => $basicTests
                ];
            }

            $deviceInfo = [];

            // Obtenemos información del dispositivo de manera segura
            try {
                $rawVersion = $this->zk->version();
                $deviceInfo['version'] = $rawVersion ? htmlspecialchars($rawVersion, ENT_QUOTES, 'UTF-8') : 'No disponible';
            } catch (\Exception $e) {
                $deviceInfo['version'] = 'Error: ' . htmlspecialchars($e->getMessage());
            }

            try {
                $rawSerial = $this->zk->serialNumber();
                $deviceInfo['serial'] = $rawSerial ? htmlspecialchars($rawSerial, ENT_QUOTES, 'UTF-8') : 'No disponible';
            } catch (\Exception $e) {
                $deviceInfo['serial'] = 'Error: ' . htmlspecialchars($e->getMessage());
            }

            try {
                $rawName = $this->zk->deviceName();
                $deviceInfo['name'] = $rawName ? htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8') : 'No disponible';
            } catch (\Exception $e) {
                $deviceInfo['name'] = 'Error: ' . htmlspecialchars($e->getMessage());
            }

            // Aseguramos desconexión limpia
            try {
                $this->zk->disconnect();
            } catch (\Exception $e) {
                Log::warning('Error al desconectar: ' . $e->getMessage());
            }

            return [
                'status' => true,
                'message' => 'Pruebas completadas',
                'network_tests' => $basicTests,
                'device_info' => $deviceInfo
            ];

        } catch (\Exception $e) {
            Log::error('Error en prueba de dispositivo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => htmlspecialchars($e->getMessage()),
                'details' => [
                    'ip' => $ip ?? null,
                    'port' => $port ?? null,
                    'error_type' => get_class($e)
                ]
            ];
        }
    }
    

    public function testDirectConnection()
    {
        try {
            $ip = config('zkteco.devices.default.ip');
            $port = config('zkteco.devices.default.port');
            
            Log::info('Iniciando prueba de conexión básica', ['ip' => $ip, 'port' => $port]);
    
            // Crear socket UDP
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (!$socket) {
                return [
                    'status' => false,
                    'error' => 'No se pudo crear el socket: ' . socket_strerror(socket_last_error())
                ];
            }
    
            // Establecer timeout
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
    
            // Comando de conexión básico para K30
            $command = pack('H*', '5050827D1001');  // Comando específico para K30
            
            $result = socket_sendto($socket, $command, strlen($command), 0, $ip, $port);
            
            if ($result === false) {
                socket_close($socket);
                return [
                    'status' => false,
                    'error' => 'Error al enviar comando: ' . socket_strerror(socket_last_error($socket))
                ];
            }
    
            // Intentar recibir respuesta
            $response = '';
            $from = '';
            $port = 0;
            socket_recvfrom($socket, $response, 1024, 0, $from, $port);
            
            socket_close($socket);
    
            return [
                'status' => true,
                'command_sent' => bin2hex($command),
                'response' => $response ? bin2hex($response) : 'No response',
                'response_length' => strlen($response),
                'from_ip' => $from,
                'from_port' => $port
            ];
    
        } catch (\Exception $e) {
            Log::error('Error en prueba de conexión', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function testBasicConnectivity()
    {
        try {
            $ip = config('zkteco.devices.default.ip');
            $port = config('zkteco.devices.default.port');
            
            // Test 1: Ping
            exec("ping -n 1 $ip", $pingOutput, $pingStatus);
            
            // Limpiamos la salida del ping
            $cleanPingOutput = array_map(function($line) {
                return preg_replace('/[^\x20-\x7E\r\n]/', '', $line);
            }, $pingOutput);
            
            // Test 2: Puerto UDP con mejor manejo de errores
            $socketStatus = false;
            $socketError = null;
            
            try {
                $socket = @fsockopen('udp://' . $ip, $port, $errno, $errstr, 1);
                if ($socket) {
                    $socketStatus = true;
                    fclose($socket);
                } else {
                    $socketError = "$errno: $errstr";
                }
            } catch (\Exception $e) {
                $socketError = $e->getMessage();
            }
            
            $result = [
                'ip' => $ip,
                'port' => $port,
                'ping_successful' => $pingStatus === 0,
                'ping_output' => $cleanPingOutput,
                'socket_connection' => $socketStatus,
                'socket_error' => $socketError
            ];
    
            Log::info('Resultado de prueba de conectividad', $result);
            
            return $result;
    
        } catch (\Exception $e) {
            Log::error('Error en prueba de conectividad', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function testMultipleCommands()
    {
        $ip = config('zkteco.devices.default.ip');
        $port = config('zkteco.devices.default.port');
        $results = [];
        
        $commands = [
            'platform' => chr(0x50),  // Get Platform
            'version' => chr(0x76),   // Get Version
            'osversion' => chr(0x6C), // Get OS Version
            'connect' => chr(0x63)    // Connect Command
        ];

        foreach ($commands as $name => $cmd) {
            $socket = @fsockopen('udp://' . $ip, $port, $errno, $errstr, 1);
            if ($socket) {
                stream_set_timeout($socket, 1);
                fwrite($socket, str_pad($cmd, 4, chr(0)));
                $response = fread($socket, 1024);
                fclose($socket);
                
                $results[$name] = [
                    'sent' => bin2hex(str_pad($cmd, 4, chr(0))),
                    'received' => bin2hex($response),
                    'length' => strlen($response)
                ];
            } else {
                $results[$name] = ['error' => "$errno: $errstr"];
            }
        }
        
        return $results;
    }

    public function getDeviceInfo()
    {
        return [
            'device_ip' => config('zkteco.devices.default.ip'),
            'device_port' => config('zkteco.devices.default.port'),
            'comm_key' => config('zkteco.devices.default.comm_key'),
            'php_version' => PHP_VERSION,
            'extensions' => get_loaded_extensions(),
            'socket_support' => function_exists('fsockopen'),
            'udp_support' => in_array('udp', stream_get_transports())
        ];
    }

    public function checkZKTimeConnection()
    {
        try {
            $ip = config('zkteco.devices.default.ip');
            
            // Verifica procesos que puedan estar usando el puerto
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("netstat -ano | findstr " . $ip, $output, $returnVal);
            } else {
                exec("netstat -nu | grep " . $ip, $output, $returnVal);
            }
            
            return [
                'status' => true,
                'active_connections' => $output,
                'has_active_connection' => !empty($output)
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function testNetworkConfig()
    {
        try {
            $ip = config('zkteco.devices.default.ip');
            $results = [];
            
            // Test 1: Verificar si hay firewall bloqueando
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("netsh advfirewall firewall show rule name=all | findstr /i \"UDP 4370\"", $firewallOutput);
                $results['firewall_rules'] = $firewallOutput;
            }
            
            // Test 2: Verificar ruta al dispositivo
            exec("tracert -d -h 3 " . $ip, $tracertOutput);
            $results['route'] = $tracertOutput;
            
            // Test 3: Verificar puertos UDP abiertos en el dispositivo
            exec("nmap -sU -p 4370 " . $ip, $nmapOutput);
            $results['port_scan'] = $nmapOutput;
            
            return [
                'status' => true,
                'tests' => $results
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function testFirewall()
    {
        $output = [];
        $returnVar = 0;

        exec("netsh advfirewall firewall show rule name=all | findstr \"UDP 4370\"", $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json(['error' => 'Error al ejecutar el comando.']);
        }

        // Verificar la codificación de cada línea en la salida
        foreach ($output as $key => $line) {
            if (!mb_check_encoding($line, 'UTF-8')) {
                $output[$key] = mb_convert_encoding($line, 'UTF-8', 'auto');
            }
        }

        return response()->json($output);
    }

    public function testRawConnection()
{
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 10, 'usec' => 0]);
    
    // Comando básico de conexión para K30/D
    $command = pack('HHHH', '50', '50', '82', '7D');
    
    socket_sendto($socket, $command, strlen($command), 0, '192.168.0.30', 4370);
    
    $from = '';
    $port = 0;
    $buf = '';
    
    $bytes = socket_recvfrom($socket, $buf, 1024, 0, $from, $port);
    
    return [
        'sent' => bin2hex($command),
        'received' => bin2hex($buf),
        'bytes' => $bytes,
        'from' => $from,
        'port' => $port
    ];
}
}