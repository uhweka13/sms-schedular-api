<?php

namespace App\Http\Controllers;

use App\Services\Auth\AuthService;
use App\Services\Common\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private $authService;
    private $responseService;

    function __construct(AuthService $authService, ResponseService $responseService)
    {
        $this->authService = $authService;
        $this->responseService = $responseService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->responseService->failedValidation($validator);
        }

        return $this->authService->register($request);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->responseService->failedValidation($validator);
        }

        return $this->authService->login($request);
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->responseService->failedValidation($validator);
        }

        return $this->authService->sendOtp($request);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->responseService->failedValidation($validator);
        }

        return $this->authService->verifyOtp($request);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->responseService->failedValidation($validator);
        }

        return $this->authService->updatePassword($request);
    }
}
