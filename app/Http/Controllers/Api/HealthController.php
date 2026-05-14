<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class HealthController extends Controller
{
    #[OA\Get(
        path: "/api/health",
        summary: "Health Check API",
        tags: ["Health"],
        responses: [
            new OA\Response(
                response: 200,
                description: "API is working"
            )
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'API working',
            'project' => 'AI Recruitment Platform',
            'version' => '1.0.0',
        ]);
    }
}