<?php

namespace App\Services\Auth;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use App\Services\Common\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class AuthService
{
    private $responseService;

    function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function register($request)
    {
        try {

            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $this->sendOtpNotification($user->email);

            return $this->responseService->success($user, 'User registration successful. And OTP has been sent to your email');
        } catch (\Throwable $th) {
            //throw $th;
            return $this->responseService->error("", $th->getMessage());
        }
    }

    public function login($request)
    {
        try {

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return $this->responseService->error("", "Invalid credentials");
            }

            if ($user->email_verified_at == null) {
                $this->sendOtpNotification($request->email);
                return $this->responseService->error("Email not verified an OTP has been sent to your email. Check your email to continue", "Verify your account to proceed");
            }

            $token = $user->createToken('api-token')->plainTextToken;

            $data = [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ];

            return $this->responseService->success($data, "Login successful");
        } catch (\Throwable $th) {
            //throw $th;
            return $this->responseService->error("", $th->getMessage());
        }
    }

    public function sendOtpNotification($email)
    {
        try {

            $checkEmail = User::where('email', $email)->first();

            if ($checkEmail == null) {
                return response()->json(['message' => 'user not found'], 404);
            }

            $four_digit_random_number = random_int(1000, 9999);

            $checkOtp = Otp::where('email', $checkEmail->email)->first();
            if ($checkOtp != null) {
                $checkOtp->delete();
            }

            $saveOtp = new Otp();
            $saveOtp->otp = $four_digit_random_number;
            $saveOtp->email = $email;
            $saveOtp->expireDate = Carbon::now()->addMinutes(10);
            $saveOtp->save();

            $details['email'] = $email;
            $details['otp'] = $four_digit_random_number;
            $details['name'] = User::where('email', $email)->first()->name;
            Notification::route('mail', $email)
                ->notify(new SendOtpNotification($four_digit_random_number));
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function verifyOtp(Request $request)
    {
        try {

            $getSentOtp = Otp::where('email', $request->email)->first();

            $date = Carbon::parse($getSentOtp->updated_at);
            $now = Carbon::now();

            $diff = $now->diffInMinutes($date, false);

            if (abs($diff) <= 10) {

                if ($getSentOtp->otp == $request->otp) {

                    $updateUserVerify = User::where('email', $request->email)->first();
                    $updateUserVerify->email_verified_at = Carbon::now();
                    $updateUserVerify->save();
                    return $this->responseService->success('Verified successfully', 'Verified successfully');
                } else {

                    return $this->responseService->success('wrong otp', 'wrong otp');
                }
            } else {

                return $this->responseService->success('otp expired', 'otp expired');
            }
        } catch (\Throwable $th) {

            return $this->responseService->error('unauthorized', $th->getMessage());
        }
    }

    public function sendOtp(Request$request)
    {
        try {

            $emailCheck = User::where('email', $request->email)->first();
            if ($emailCheck == null) {
                return $this->responseService->error("No account associated with this email", "No account associated with this email");
            }

            $this->sendOtpNotification($request->email);

            return $this->responseService->success('And OTP has been sent to your email', 'And OTP has been sent to your email');

            return 'success';
        } catch (\Throwable $th) {
            return $th;
            return $this->responseService->errorResponse("Failed", "Failed");
        }
    }

    public function updatePassword(Request $request)
    {
        try {

            $getSentOtp = Otp::where('email', $request->email)->first();

            $date = Carbon::parse($getSentOtp->expireDate);
            $now = Carbon::now();

            $diff = $now->diffInSeconds($date, false);

            if ($diff > 0) {

                if ($getSentOtp->otp == $request->otp) {

                    $updateUserVerify = User::where('email', $request->email)->first();
                    $updateUserVerify->password = bcrypt($request->password);
                    $updateUserVerify->save();
                    return $this->responseService->success('password reset was successful', 'password reset was successful');
                } else {

                    return $this->responseService->error('invalid otp', 'invalid otp');
                }
            } else {

                return $this->responseService->error('otp expired', 'otp expired');
            }
        } catch (\Throwable $th) {
            return $this->responseService->error('unauthorized', 'unauthorized');
        }
    }
}
