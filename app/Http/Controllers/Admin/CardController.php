<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\User;

use Illuminate\Support\Facades\DB;

class CardController extends Controller
{
    public function index()
    {
        $cards = Card::with('passenger')->paginate(15); 
        return view('admin.cards.index', compact('cards'));
    }


    public function create()
    {
        $passengers = User::where('role','passenger')->where('active',1)->get();
        return view('admin.cards.create', compact('passengers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'uid' => 'required|unique:cards',
            'balance' => 'nullable|numeric|min:0',
            'passenger_id' => 'required|exists:users,id'
        ]);

        Card::create([
            'uid' => $request->uid,
            'balance' => $request->balance ?? 0,
            'passenger_id' => $request->passenger_id,
            'active' => true
        ]);

        return redirect()->route('admin.cards.index')->with('success','Tarjeta creada correctamente');
    }


    public function edit(Card $card)
    {
        $passengers = User::where('role','passenger')->where('active',1)->get();
        return view('admin.cards.edit', compact('card','passengers'));
    }

    public function update(Request $request, Card $card)
    {
        $request->validate([
            'uid' => "required|unique:cards,uid,{$card->id}",
            'passenger_id' => 'required|exists:users,id',
            'active' => 'required|boolean'
        ]);

        $card->update([
            'uid' => $request->uid,
            'passenger_id' => $request->passenger_id,
            'active' => $request->active
        ]);

        return redirect()->route('admin.cards.index')->with('success','Tarjeta actualizada');
    }

    public function recharge(Request $request, Card $card)
    {
        \Log::info('Recharge method called', [
            'card_id' => $card->id,
            'request_data' => $request->all()
        ]);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = $request->amount;

        try {
            DB::transaction(function () use ($card, $amount) {
                \Log::info('Starting transaction', ['card_balance_before' => $card->balance]);

                // Aumentar el saldo de la tarjeta
                $card->balance += $amount;
                $card->save();

                \Log::info('Card balance updated', ['card_balance_after' => $card->balance]);

                // Crear el registro de la transacción
                $card->transactions()->create([
                    'amount' => $amount,
                    'type' => 'recharge',
                    'description' => 'Recarga de saldo por administrador.',
                ]);

                \Log::info('Transaction record created');

                // Registrar evento de recarga para notificar al pasajero
                \App\Models\PaymentEvent::create([
                    'trip_id' => null,
                    'card_uid' => $card->uid,
                    'card_id' => $card->id,
                    'passenger_id' => $card->passenger_id,
                    'event_type' => 'recharge',
                    'amount' => $amount,
                    'required_amount' => null,
                    'message' => 'Recarga realizada con éxito. Monto abonado: ' . number_format($amount, 2) . ' Bs'
                ]);

                \Log::info('Payment event created');
            });

            \Log::info('Recharge successful');
            return back()->with('success', 'Recarga de ' . $amount . ' Bs. realizada correctamente.');

        } catch (\Throwable $e) {
            \Log::error('Recharge failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Ocurrió un error durante la recarga: ' . $e->getMessage()]);
        }
    }


    public function destroy(Card $card)
    {
        $card->delete();
        return redirect()->route('admin.cards.index')->with('success','Tarjeta eliminada');
    }
}
