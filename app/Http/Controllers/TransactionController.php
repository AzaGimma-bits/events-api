<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function store (Request $request){
        //validaciones
        $validated = $request->validate([
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id|different:sender_id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        //usuarios
        $sender = User::find($validated['sender_id']);
        $receiver = User::find($validated['receiver_id']);
        $amount = $validated['amount'];

        //Límite diario de transferencia
        $totalToday = Transaction::where('sender_id', $sender->id)
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');
        
            if (($totalToday + $amount) > 5000){
                return response()->json([
                    'message' => 'Límite diario excedido'
                ],400);
            }

        //saldo insuficiente
        if ($sender->balance < $amount){
            return response()->json([
                'message' => 'Saldo insuficiente'
            ],400);
        }
        //transacciones duplicadas
        $duplicate = Transaction::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('amount', $amount)
            ->where('created_at', '>=', now()->subMinutes(1))
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Duplicado detectado'
            ],400);
        }

        //Realizar transacción
        DB::transaction(function() use ($sender, $receiver, $amount){
            $sender->decrement('balance', $amount);
            $receiver->increment('balance', $amount);
            
            Transaction::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
            ]);
        });

        return response()->json([
            'message' => 'Transferencia realizada con exito'
        ],201);
    }
    //Consultas estadisticas
    public function stats(){
        $stats = Transaction::select(
            'sender_id',
            DB::raw('SUM(amount) as total'),
            DB::raw('AVG(amount) as average')
        )
        ->groupBy('sender_id')
        ->get();
        return response()->json($stats);
    }

    //Exportación CSV
    public function export(){
        $transactiones = Transaction::all();
        $csvData = [];

        $csvData[]= ['sender_id','receiver_id','amount', 'date'];

        foreach($transactiones as $i) {
            $csvData[]=[
                $i->sender_id,
                $i->receiver_id,
                $i->amount,
                $i->created_at->toDateTimeString(), 
            ];
        }
        $filename = 'transaciones.csv';
        $handle = fopen($filename, 'w+');

        foreach($csvData as $row){
            fputcsv($handle, $row, ';');
        }
        fclose($handle);
        return response()->download($filename)->deleteFileAfterSend(true);
    }
}

