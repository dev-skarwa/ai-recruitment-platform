<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Services\AuthService;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService
    ) {
    }

    #[OA\Post(
        path: "/api/v1/register",
        summary: "Register User",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(
                        property: "name",
                        type: "string",
                        example: "Shruti Karwa"
                    ),
                    new OA\Property(
                        property: "email",
                        type: "string",
                        example: "shruti@example.com"
                    ),
                    new OA\Property(
                        property: "password",
                        type: "string",
                        example: "Password@123"
                    ),
                    new OA\Property(
                        property: "password_confirmation",
                        type: "string",
                        example: "Password@123"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User registered successfully"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            )
        ]
    )]
    public function register(RegisterRequest $request)
    {
        try {
            $data = $this->authService->register($request->validated());

            return $this->successResponse(
                $data,
                'User registered successfully',
                201
            );
        } catch (ValidationException $e) {
            // Handle validation errors (e.g., email already exists)
            return $this->errorResponse(
                'Validation failed: ' . json_encode($e->validator->errors()->messages()),
                422
            );
        }
    }

    #[OA\Post(
        path: "/api/v1/login",
        summary: "User Login",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(
                        property: "email",
                        type: "string",
                        example: "shruti@example.com"
                    ),
                    new OA\Property(
                        property: "password",
                        type: "string",
                        example: "Password@123"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful"
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            )
        ]
    )]
    public function login(LoginRequest $request)
    {
        $data = $this->authService->login($request->validated());

        if (!$data) {
            return $this->errorResponse(
                'Invalid credentials',
                401
            );
        }

        return $this->successResponse(
            $data,
            'Login successful'
        );
    }

    #[OA\Post(
        path: "/api/v1/logout",
        summary: "Logout User",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout successful"
            )
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    #[OA\Get(
        path: "/api/v1/profile",
        summary: "Authenticated User Profile",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Profile fetched successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            )
        ]
    )]

    public function profile(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $this->successResponse(
            $request->user(),
            'Profile fetched successfully'
        );
    }
}