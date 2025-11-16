<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $per = intval($request->get('per', 20));

        $cardIds = $user->cards()->pluck('id')->toArray();

        $tx = Transaction::with(['ruta','trip','bus'])
            ->whereIn('card_id', $cardIds)
            ->orderBy('created_at','desc')
            ->paginate($per);

        return response()->json($tx);
    }

    /**
     * Retorna solo las recargas del usuario autenticado
     */
    public function recharges(Request $request)
    {
        $user = $request->user();
        $per = intval($request->get('per', 20));
        $cardIds = $user->cards()->pluck('id')->toArray();

        $recharges = Transaction::whereIn('card_id', $cardIds)
                                ->where('type', 'recharge')
                                ->orderBy('created_at', 'desc')
                                ->paginate($per);

        return response()->json($recharges);
    }
}
