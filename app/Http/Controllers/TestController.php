<?php

namespace App\Http\Controllers;

use App\Services\ZKTecoTest;
use Illuminate\Routing\Controller;

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
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
}