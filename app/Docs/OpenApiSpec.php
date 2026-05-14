<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "AI Recruitment Platform API Documentation",
    description: "API documentation for AI Recruitment Platform"
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Local API Server"
)]
class OpenApiSpec
{
}