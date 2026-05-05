<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// tambahan untuk handling callback dan debugging
use Illuminate\Support\Facades\DB; // Tambahkan ini
use Illuminate\Support\Facades\Log; // Tambahkan ini untuk debugging

class CobaMidtransController extends Controller
{
    //contoh sampel sederhana method untuk tester fungsionalitas midtrans
    public function cekmidtrans(Request $request)
    {
        // definisikan parameter midtrans
        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = false;
        // Set sanitization on (default)
        \Midtrans\Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        \Midtrans\Config::$is3ds = true;

        // Optional
        $item1_details = array(
            'id' => 'a1',
            'price' => 10000,
            'quantity' => 3,
            'name' => "Apple"
        );

        // Optional
        $item2_details = array(
            'id' => 'a2',
            'price' => 20000,
            'quantity' => 1,
            'name' => "Orange"
        );

        // Optional
        $item_details = array ($item1_details, $item2_details);

        // Optional, remove this to display all available payment methods
        // $enable_payments = array("bca_va","bni_va");

        $params = array(
            'transaction_details' => array(
                'order_id' => rand(), //idpesanan ini nanti dpt diambil dari no_pesanan
                'gross_amount' => 50000,
            ),
            'customer_details' => array(
                'first_name' => 'Wiro',
                'last_name' => 'Sableng',
                'email' => 'wirosableng@gmail.com',
                'phone' => '0821142334',
            ),
            'item_details' => $item_details,
            // 'enabled_payments' => $enable_payments,
        );
         
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        // dd($snapToken);
        return view(    'midtrans.viewsampel',
                        [
                            'snap_token' => $snapToken,
                        ]
        );
    } 

    //contoh sampel sederhana method untuk tester fungsionalitas midtrans
    public function cekmidtranscallback(Request $request)
    {
        // definisikan parameter midtrans
        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = false;
        // Set sanitization on (default)
        \Midtrans\Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        \Midtrans\Config::$is3ds = true;

        // Optional
        $item1_details = array(
            'id' => 'a1',
            'price' => 10000,
            'quantity' => 3,
            'name' => "Apple"
        );

        // Optional
        $item2_details = array(
            'id' => 'a2',
            'price' => 20000,
            'quantity' => 1,
            'name' => "Orange"
        );

        // Optional
        $item_details = array ($item1_details, $item2_details);

        // Optional, remove this to display all available payment methods
        // $enable_payments = array("bca_va","bni_va");
        $oid = rand();
        $grossAmount = 50000;

        // RUMUS ASLI MIDTRANS: SHA512(order_id + status_code + gross_amount + server_key)
        // Untuk simulasi "settlement", status_code-nya adalah 200
        $signatureAsli = hash("sha512", $oid . '200' . $grossAmount . \Midtrans\Config::$serverKey);

        $params = array(
            'transaction_details' => array(
                'order_id' => $oid, //idpesanan ini nanti dpt diambil dari no_pesanan
                'gross_amount' => 50000,
            ),
            'customer_details' => array(
                'first_name' => 'Wiro',
                'last_name' => 'Sableng',
                'email' => 'wirosableng@gmail.com',
                'phone' => '0821142334',
            ),
            'item_details' => $item_details,
            // 'enabled_payments' => $enable_payments,
        );
         
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        // dd($snapToken);
        return view(    'midtrans.viewsampelcallback',
                        [
                            'snap_token' => $snapToken,
                            'order_id' => $oid, // Kirim juga order_id ke view untuk simulasi
                            'gross_amount' => $grossAmount,
                            'signature'  => $signatureAsli
                        ]
        );
    }

    /**
     * METHOD TAMBAHAN UNTUK CALLBACK
     */
    public function handleCallback(Request $request)
    {
        // 1. Ambil server key dari env
        $serverKey = env('MIDTRANS_SERVER_KEY');

        // 2. Buat signature key untuk verifikasi keamanan (Wajib)
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 3. Simpan data ke tabel payment_logs
        try {
            DB::table('payment_logs')->insert([
                'order_id'           => $request->order_id,
                // 'transaction_id'     => $request->transaction_id, // ID transaksi dari Midtrans
                'transaction_status' => $request->transaction_status,
                'payment_type'       => $request->payment_type,
                'gross_amount'       => $request->gross_amount,
                'raw_response'       => json_encode($request->all()), // Simpan semua data JSON
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // Opsional: Jika status 'settlement', update tabel pesanan utama Anda
            if ($request->transaction_status == 'settlement') {
                // DB::table('orders')->where('invoice', $request->order_id)->update(['status' => 'Lunas']);
            }

            return response()->json(['message' => 'Success'], 200);

        } catch (\Exception $e) {
            Log::error('Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error saving to database'], 500);
        }
    }
}