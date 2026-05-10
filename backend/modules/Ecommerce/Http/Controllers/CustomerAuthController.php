<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Domain\Models\Customer;

class CustomerAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|string|min:8',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:32',
        ]);

        $customer = Customer::query()->create($data);
        $token = $customer->createToken('storefront')->plainTextToken;
        return response()->json(['token' => $token, 'customer' => $customer], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = Customer::query()->where('email', $data['email'])->first();
        if (! $customer || ! Hash::check($data['password'], $customer->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        $token = $customer->createToken('storefront')->plainTextToken;
        return response()->json(['token' => $token, 'customer' => $customer]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
