<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TokenRequest;

class TokenController
{
    public function store(TokenRequest $request)
    {
        $token = auth()->user()->createToken($request->name);
 
        return response()->json([
            'token'            => $token->accessToken,
            'plain_text_token' => $token->plainTextToken,
        ], 201);
    }
 
    public function destroy(int $tokenId)
    {
        auth()->user()
            ->tokens()
            ->findOrFail($tokenId)
            ->delete();
 
        return response()->json(null, 204);
    }
}
