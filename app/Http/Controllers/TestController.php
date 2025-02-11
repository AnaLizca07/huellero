<?php

namespace App\Http\Controllers;

use App\Services\ZKTecoTest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{

    private $zkTecoTest;

    public function __construct(ZKTecoTest $zKTecoTest)
    {
        $this->zkTecoTest = $zKTecoTest;
    }


    public function testDevice()
    {
        try {
            $result = $this->zkTecoTest->testDevice();
            
            // Aseguramos que todos los strings sean UTF-8 válidos
            $sanitizedResult = json_decode(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE), true);
            
            return response()->json($sanitizedResult);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => utf8_encode($e->getMessage()),
                'debug_info' => [
                    'error_type' => get_class($e),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    public function testUDP()
    {
        try{
        $result = $this->zkTecoTest->testDirectConnection();
        return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function testConnectivity()
    {
        return response()->json($this->zkTecoTest->testBasicConnectivity());
    }

    public function testCommands()
    {
        return response()->json($this->zkTecoTest->testMultipleCommands());
    }

    public function getDeviceInfo()
    {
        return response()->json($this->zkTecoTest->getDeviceInfo());
    }

    public function checkZKTime()
    {
        return response()->json($this->zkTecoTest->checkZKTimeConnection());
    }

    public function testNetwork()
    {
        return response()->json($this->zkTecoTest->testNetworkConfig());
    }

    public function firewall()
    {
        return response()->json($this->zkTecoTest->testFirewall());
    }
    public function testRawConnection()
    {
        return response()->json($this->zkTecoTest->testRawConnection());
    }
}