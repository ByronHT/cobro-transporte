<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user()->load('cards');
        $primaryCard = $user->cards->first();
        $balance = $primaryCard ? $primaryCard->balance : 0.00;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'balance' => number_format($balance, 2, '.', ''),
            'primary_card_id' => $primaryCard ? $primaryCard->id : null,
        ]);
    }
}
