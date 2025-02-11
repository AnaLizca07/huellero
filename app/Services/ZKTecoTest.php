<?php

namespace App\Services;

use App\Services\ZKTecoService;
use Rats\Zkteco\Lib\ZKTeco;

class ZKTecoTest extends ZKTecoService
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

    private function pingDevice()
    {
        $ip = config('zkteco.devices.default.ip');
        exec("ping -n 1 " . $ip, $output, $status);
        return $status === 0;
    }

    // Métodos de prueba
    public function testDevice()
    {
        try {
            if (!$this->connect()) {
                return ['status' => false, 'message' => 'No se pudo conectar al dispositivo'];
            }

            $info = [
                'version' => $this->zk->version(),
                'serialNumber' => $this->zk->serialNumber(),
                'deviceName' => $this->zk->deviceName(),
                'userCount' => count($this->zk->getUser()),
                'pingResponse' => $this->pingDevice()
            ];

            $this->zk->disconnect();
            return ['status' => true, 'info' => $info];

        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function testDirectConnection()
    {
        try {
            $socket = fsockopen('udp://' . config('zkteco.devices.default.ip'), 
                            config('zkteco.devices.default.port'), 
                            $errno, 
                            $errstr, 
                            5);
            
            if (!$socket) {
                return ['status' => false, 'error' => "Error $errno: $errstr"];
            }

            // Comando básico de prueba (Get Platform)
            $command = chr(0x50) . chr(0x00) . chr(0x00) . chr(0x00);
            fwrite($socket, $command);
            
            $response = fread($socket, 1024);
            fclose($socket);
            
            return ['status' => true, 
                    'response' => bin2hex($response),
                    'length' => strlen($response)];
        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function testBasicConnectivity()
    {
        $ip = config('zkteco.devices.default.ip');
        $port = config('zkteco.devices.default.port');
        
        // Test 1: Ping
        exec("ping -n 1 $ip", $pingOutput, $pingStatus);
        
        // Test 2: Puerto UDP
        $socket = @fsockopen('udp://' . $ip, $port, $errno, $errstr, 1);
        $socketStatus = $socket !== false;
        if ($socket) {
            fclose($socket);
        }
        
        return [
            'ip' => $ip,
            'port' => $port,
            'ping_successful' => $pingStatus === 0,
            'ping_output' => $pingOutput,
            'socket_connection' => $socketStatus,
            'socket_error' => $socketStatus ? null : "$errno: $errstr"
        ];
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
}