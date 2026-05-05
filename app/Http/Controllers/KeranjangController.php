<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// tambahan untuk akses ke model barang
use App\Models\Barang; //untuk akses kelas model barang

// tambahan untuk memproses pembayaran menggunakan midtrans
use App\Models\Penjualan; //untuk akses kelas model penjualan
use App\Models\PenjualanBarang; //untuk akses kelas model penjualan
use App\Models\Pembayaran; //untuk akses kelas model pembayaran
use App\Models\Pembeli; //untuk akses kelas model pembeli
use Illuminate\Support\Facades\DB; //untuk menggunakan db
use Illuminate\Support\Facades\Auth; //agar bisa mengakses session user_id dari user yang login
use Illuminate\Support\Facades\Log; //tambahan log
use Illuminate\Support\Str; //untuk pemrosesan string

// tambahan untuk akses WA
use App\Http\Controllers\NotificationController;

class KeranjangController extends Controller
{
    // method daftar barang
    public function daftarbarang()
    {
        // awal
        // return view( 'galeri');

        // minggu ke 3
        // ambil data barang
        // $barang = Barang::all();
        // // kirim ke halaman view
        // return view('galeri2',
        //                 [ 
        //                     'barang'=>$barang,
        //                 ]
        //             ); 

        // minggu ke-8 modul pembayaran di customer menggunakan midtrans
        // ambil id user dari session user_id yang masuk 
        $id_user = Auth::user()->id;

        // dapatkan id_pembeli dari user_id di tabel users sesuai data yang login
        $pembeli = Pembeli::where('user_id', $id_user)
                ->select(DB::raw('id'))
                ->first();
        $id_pembeli = $pembeli->id;

        // ambil data barang
        $barang = Barang::all();

        // ambil data jumlah barang yang dibeli oleh pembeli id sesuai dengan session yg ditemukan
        // dan belum terbayar
        $jmlbarangdibeli = DB::table('penjualan')
                            ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                            ->join('pembeli', 'penjualan.pembeli_id', '=', 'pembeli.id')
                            ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                            ->select(DB::raw('COUNT(DISTINCT barang_id) as total'))
                            ->where('penjualan.pembeli_id', '=', $id_pembeli) 
                            ->where(function($query) {
                                $query->where('pembayaran.gross_amount', 0)
                                      ->orWhere(function($q) {
                                          $q->where('pembayaran.status_code', '!=', 200)
                                            ->where('pembayaran.jenis_pembayaran', 'pg');
                                      });
                            })
                            ->get();

        // ambil total belanja yang belum terbayar
        $t = DB::table('penjualan')
            ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
            ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
            ->select(DB::raw('SUM(harga_jual * jml) as total'))
            ->where('penjualan.pembeli_id', '=', $id_pembeli) 
            ->where(function($query) {
                $query->where('pembayaran.gross_amount', 0)
                        ->orWhere(function($q) {
                            $q->where('pembayaran.status_code', '!=', 200)
                            ->where('pembayaran.jenis_pembayaran', 'pg');
                        });
            })
            ->first();

        // kirim ke halaman view
        return view('galeri',
                        [ 
                            'barang'=>$barang,
                            'total_belanja' => $t->total ?? 0,
                            'jmlbarangdibeli' => $jmlbarangdibeli[0]->total ?? 0
                        ]
                    ); 

    }

    // halaman tambah keranjang
    public function tambahKeranjang(Request $request)
    {
        
        try {
            $request->validate([
                'product_id' => 'required|exists:barang,id',
                'quantity' => 'required|integer|min:1'
            ]);
    
            $id_user = Auth::user()->id;

            // dapatkan id_pembeli dari user_id di tabel users sesuai data yang login
            $pembeli = Pembeli::where('user_id', $id_user)
                            ->select(DB::raw('id'))
                            ->first();
            $id_pembeli = $pembeli->id;

            // cek di database apakah ada nomor faktur yang masih aktif
            // dilihat dari pembayaran yg masih 0
            
            try{
                $product = Barang::find($request->product_id); //ambil data barang simpan di tabel product
                if (!$product) {
                    return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan!']);
                }
                $harga = $product->harga_barang;
                $jumlah = (int) $request->quantity;
                $barang_id = $request->product_id;

               // Cek apakah ada penjualan dengan gross_amount = 0
                $penjualanExist = DB::table('penjualan')
                ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                ->where('penjualan.pembeli_id', $id_pembeli)
                ->where(function($query) {
                    $query->where('pembayaran.gross_amount', 0)
                          ->orWhere(function($q) {
                              $q->where('pembayaran.status_code', '!=', 200)
                                ->where('pembayaran.jenis_pembayaran', 'pg');
                          });
                })
                ->select('penjualan.id') // Ambil ID saja untuk dicek
                ->first();

                if (!$penjualanExist) {
                    // Buat penjualan baru jika tidak ada
                    $penjualan = Penjualan::create([
                        'no_faktur'   => Penjualan::getKodeFaktur(),
                        'tgl'         => now(),
                        'pembeli_id'  => $id_pembeli,
                        'tagihan'     => 0,
                        'status'      => 'pesan',
                    ]);

                    // Buat pembayaran baru
                    $pembayaran = Pembayaran::create([
                        'penjualan_id'      => $penjualan->id,
                        'tgl_bayar'         => now(),
                        'jenis_pembayaran'  => 'pg', //tipe pembayaran menggunakan payment gateway
                        'gross_amount'      => 0,
                    ]);
                }else{
                    // data penjualan ditemukan
                    $penjualan = Penjualan::find($penjualanExist->id);
                }


                // Tambahkan barang ke penjualan_barang
                PenjualanBarang::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $barang_id,
                    'jml' => $jumlah,
                    'harga_beli'=>$harga,
                    'harga_jual'=>$harga*1.2,
                    'tgl'=>date('Y-m-d')
                ]);

                // Update total tagihan pada tabel penjualan karena ada pembelian barang lain
                // $penjualan->tagihan = PenjualanBarang::where('penjualan_id', $penjualan->id)->sum('total');
                $tagihan = DB::table('penjualan')
                ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                ->select(DB::raw('SUM(harga_jual * jml) as total'))
                ->where('penjualan.pembeli_id', '=', $id_pembeli) 
                ->where(function($query) {
                    $query->where('pembayaran.gross_amount', 0)
                          ->orWhere(function($q) {
                              $q->where('pembayaran.status_code', '!=', 200)
                                ->where('pembayaran.jenis_pembayaran', 'pg');
                          });
                })
                ->first();
                $penjualan->tagihan = $tagihan->total;
                $penjualan->save();

                // update stok barang di master data barang, kurangi jumlahnya dengan jml yang telah dipesan
                Barang::where('id', $barang_id)->decrement('stok', $jumlah);

                // hitung total barang
                $jmlbarangdibeli = DB::table('penjualan')
                            ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                            ->join('pembeli', 'penjualan.pembeli_id', '=', 'pembeli.id')
                            ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                            ->select(DB::raw('COUNT(DISTINCT barang_id) as total'))
                            ->where('penjualan.pembeli_id', '=', $id_pembeli) 
                            ->where(function($query) {
                                $query->where('pembayaran.gross_amount', 0)
                                      ->orWhere(function($q) {
                                          $q->where('pembayaran.status_code', '!=', 200)
                                            ->where('pembayaran.jenis_pembayaran', 'pg');
                                      });
                            })
                            ->get();

                // DB::commit(); //commit ke database
                return response()->json(['success' => true, 'message' => 'Transaksi berhasil ditambahkan!', 
                'total' => $penjualan->tagihan, 'jmlbarangdibeli'=>$jmlbarangdibeli[0]->total ?? 0]);

            }catch(\Exception $e){
                // DB::rollBack(); //rollback jika ada error
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
    }

    // halaman lihat keranjang
    public function lihatkeranjang(){
        $id_user = Auth::user()->id;
        $email_user = Auth::user()->email; //ambil emailnya

        // dapatkan id_pembeli dari user_id di tabel users sesuai data yang login
        $pembeli = Pembeli::where('user_id', $id_user)
                        ->select(DB::raw('id'))
                        ->first();
        $id_pembeli = $pembeli->id;
        $nama_pembeli = $pembeli->nama_pembeli;
        $telepon = '0'.$pembeli->telepon;
        // dd(var_dump($id_pembeli));

        $barang = DB::table('penjualan')
                        ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                        ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                        ->join('barang', 'penjualan_barang.barang_id', '=', 'barang.id')
                        ->join('pembeli', 'penjualan.pembeli_id', '=', 'pembeli.id')
                        ->select('penjualan.id','penjualan.no_faktur','pembeli.nama_pembeli', 'penjualan_barang.barang_id', 'barang.nama_barang','penjualan_barang.harga_jual', 
                                 'barang.foto','pembayaran.order_id',
                                  DB::raw('SUM(penjualan_barang.jml) as total_barang'),
                                  DB::raw('SUM(penjualan_barang.harga_jual * penjualan_barang.jml) as total_belanja'))
                        ->where('penjualan.pembeli_id', '=',$id_pembeli) 
                        ->where(function($query) {
                            $query->where('pembayaran.gross_amount', 0)
                                  ->orWhere(function($q) {
                                      $q->where('pembayaran.status_code', '!=', 200)
                                        ->where('pembayaran.jenis_pembayaran', 'pg');
                                  });
                        })
                        ->groupBy('penjualan.id','penjualan.no_faktur','pembeli.nama_pembeli','penjualan_barang.barang_id', 'barang.nama_barang','penjualan_barang.harga_jual',
                                  'barang.foto','pembayaran.order_id',
                                 )
                        ->get();

        // hitung jumlah total tagihan
        $ttl = 0; $jml_brg = 0; $kode_faktur = '';
        foreach($barang as $p){
            $ttl += $p->total_belanja;
            $jml_brg += 1;
            $kode_faktur = $p->no_faktur;
            $idpenjualan = $p->id;
            $odid = $p->order_id;
        }

        // cek dulu apakah sudah ada di midtrans dan belum expired
        $ch = curl_init(); 
        $login = env('MIDTRANS_SERVER_KEY');
        $password = '';
        if(isset($odid)){
            // $parts = explode('-', $odid);
            // $substring = $parts[0] . '-' . $parts[1];
            // $orderid = $substring;
            $orderid = $odid;
        }else{
            $orderid =$kode_faktur.'-'.date('YmdHis'); //FORMAT
        }
        // dd($odid);
        $URL =  'https://api.sandbox.midtrans.com/v2/'.$orderid.'/status';
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");  
        $output = curl_exec($ch); 
        curl_close($ch);    
        $outputjson = json_decode($output, true); //parsing json dalam bentuk assosiative array
        // return $outputjson;
        // dd($outputjson);
        // ambil statusnya
        if(isset($outputjson)){
            // awal
            if($outputjson['status_code']=='404' or in_array($outputjson['transaction_status'], ['expire', 'cancel', 'deny'])){
                // echo "transaksi tidak ditemukan diserver midtrans ";
                // cek jika jml datanya 0 maka jangan menjalankan payment gateway
                if($ttl>0){
                    // proses generate token payment gateway
                    $order_id = $kode_faktur.'-'.date('YmdHis');
                    
    
                    $myArray = array(); //untuk menyimpan objek array
                    $i = 1;
                    foreach($barang as $k):
                        // untuk data item detail
                        // kita perlu membuat objek dulu kemudian di masukkan ke array
                        $foo = array(
                                'id'=> $i,
                                'price' => $k->harga_jual,
                                'quantity' => $k->total_barang,
                                'name' => $k->nama_barang,
    
                        );
                        $i++;
                        // tambahkan ke myarray
                        array_push($myArray,$foo);
                    endforeach;
                    
                    \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
                    // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
                    \Midtrans\Config::$isProduction = false;
                    // Set sanitization on (default)
                    \Midtrans\Config::$isSanitized = true;
                    // Set 3DS transaction for credit card to true
                    \Midtrans\Config::$is3ds = true;
    
                    $params = array(
                        'transaction_details' => array(
                            'order_id' => $order_id, 
                            'gross_amount' => $ttl, //gross amount diisi total tagihan
                        ),
                            'customer_details' => array(
                            'first_name' => $nama_pembeli,
                            'last_name' => '',
                            'email' => $email_user,
                            'phone' => $telepon,
                         ),
                        'item_details' => $myArray,
                        'expiry' => [
                                'start_time' => date("Y-m-d H:i:s O"), // sekarang
                                'unit' => 'minutes', // bisa 'minutes', 'hours', atau 'days'
                                'duration' => 20 // expired dalam 60 menit
                        ]
                    );
    
                    $snapToken = \Midtrans\Snap::getSnapToken($params);
    
                    $pembayaran = Pembayaran::updateOrCreate(
                        ['penjualan_id' => $idpenjualan], // Cek apakah id penjualan sudah ada
                        [
                            'tgl_bayar'        => now(),
                            'jenis_pembayaran' => 'pg', // Payment Gateway
                            'order_id'         => $order_id,
                            'gross_amount'     => $ttl,
                            'status_code'      => '201', // 201 = Pending
                            'status_message'   => 'Pending payment', // Status awal
                            'transaction_id' => $snapToken, //snap tokennya di simpan di transaction id
                        
                        ]
                    );
    
                    return view( 'keranjang',
                                [
                                    'barang' => $barang,
                                    'total_tagihan' => $ttl,
                                    'jml_brg' => $jml_brg,
                                    'snap_token' => $snapToken,
                                ]
                    );
                }else{
                    // kalau transaksi kosong diarahkan saja ke depan
                    return redirect('/depan');
                }
            }else{
                // echo "transaksi ditemukan diserver midtrans, maka tinggal bayar";
    
                $tagihan = DB::table('penjualan')
                ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                ->select(DB::raw('transaction_id'))
                ->where('penjualan.pembeli_id', '=', $id_pembeli) 
                ->where(function($query) {
                    $query->where('pembayaran.gross_amount', 0)
                          ->orWhere(function($q) {
                              $q->where('pembayaran.status_code', '!=', 200)
                                ->where('pembayaran.jenis_pembayaran', 'pg');
                          });
                })
                ->first();
    
                $barang = DB::table('penjualan')
                            ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                            ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                            ->join('barang', 'penjualan_barang.barang_id', '=', 'barang.id')
                            ->join('pembeli', 'penjualan.pembeli_id', '=', 'pembeli.id')
                            ->select('penjualan.id','penjualan.no_faktur','pembeli.nama_pembeli', 'penjualan_barang.barang_id', 'barang.nama_barang','penjualan_barang.harga_jual', 
                                     'barang.foto',
                                      DB::raw('SUM(penjualan_barang.jml) as total_barang'),
                                      DB::raw('SUM(penjualan_barang.harga_jual * penjualan_barang.jml) as total_belanja'))
                            ->where('penjualan.pembeli_id', '=',$id_pembeli) 
                            ->where(function($query) {
                                $query->where('pembayaran.gross_amount', 0)
                                      ->orWhere(function($q) {
                                          $q->where('pembayaran.status_code', '!=', 200)
                                            ->where('pembayaran.jenis_pembayaran', 'pg');
                                      });
                            })
                            ->groupBy('penjualan.id','penjualan.no_faktur','pembeli.nama_pembeli','penjualan_barang.barang_id', 'barang.nama_barang','penjualan_barang.harga_jual',
                                      'barang.foto',
                                     )
                            ->get();
    
                $ttl = 0; $jml_brg = 0; $kode_faktur = '';
                foreach($barang as $p){
                    $ttl += $p->total_belanja;
                    $jml_brg += 1;
                    $kode_faktur = $p->no_faktur;
                    $idpenjualan = $p->id;
                }
    
                return view('keranjang', [
                    'barang' => $barang,
                    'total_tagihan' => $ttl,
                    'jml_brg' => $jml_brg,
                    'snap_token' => $tagihan->transaction_id
                ]);
            }
            // akhir
        }else{
            // jika sudah kadaluarsa tapi tidak ditemukan di midtrans
            $order_id = $kode_faktur.'-'.date('YmdHis');
                    
    
                    $myArray = array(); //untuk menyimpan objek array
                    $i = 1;
                    foreach($barang as $k):
                        // untuk data item detail
                        // kita perlu membuat objek dulu kemudian di masukkan ke array
                        $foo = array(
                                'id'=> $i,
                                'price' => $k->harga_jual,
                                'quantity' => $k->total_barang,
                                'name' => $k->nama_barang,
    
                        );
                        $i++;
                        // tambahkan ke myarray
                        array_push($myArray,$foo);
                    endforeach;
                    
                    \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
                    // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
                    \Midtrans\Config::$isProduction = false;
                    // Set sanitization on (default)
                    \Midtrans\Config::$isSanitized = true;
                    // Set 3DS transaction for credit card to true
                    \Midtrans\Config::$is3ds = true;
    
                    $params = array(
                        'transaction_details' => array(
                            'order_id' => $order_id, 
                            'gross_amount' => $ttl, //gross amount diisi total tagihan
                        ),
                            'customer_details' => array(
                            'first_name' => $nama_pembeli,
                            'last_name' => '',
                            'email' => $email_user,
                            'phone' => $telepon,
                         ),
                        'item_details' => $myArray,
                        'expiry' => [
                                'start_time' => date("Y-m-d H:i:s O"), // sekarang
                                'unit' => 'minutes', // bisa 'minutes', 'hours', atau 'days'
                                'duration' => 20 // expired dalam 60 menit
                        ]
                    );
    
                    $snapToken = \Midtrans\Snap::getSnapToken($params);
    
                    $pembayaran = Pembayaran::updateOrCreate(
                        ['penjualan_id' => $idpenjualan], // Cek apakah id penjualan sudah ada
                        [
                            'tgl_bayar'        => now(),
                            'jenis_pembayaran' => 'pg', // Payment Gateway
                            'order_id'         => $order_id,
                            'gross_amount'     => $ttl,
                            'status_code'      => '201', // 201 = Pending
                            'status_message'   => 'Pending payment', // Status awal
                            'transaction_id' => $snapToken, //snap tokennya di simpan di transaction id
                        
                        ]
                    );
    
                    return view( 'keranjang',
                                [
                                    'barang' => $barang,
                                    'total_tagihan' => $ttl,
                                    'jml_brg' => $jml_brg,
                                    'snap_token' => $snapToken,
                                ]
                    );
        }
        
    }

    // untuk menghapus
    public function hapus($barang_id)
    {
        $id_user = Auth::user()->id;

        // dapatkan id_pembeli dari user_id di tabel users sesuai data yang login
        $pembeli = Pembeli::where('user_id', $id_user)
                        ->select(DB::raw('id'))
                        ->first();
        $id_pembeli = $pembeli->id;

        
        $sql = "DELETE FROM penjualan_barang WHERE barang_id = ? AND penjualan_id = (SELECT penjualan.id FROM penjualan join pembayaran on (penjualan.id=pembayaran.penjualan_id) WHERE penjualan.pembeli_id = ? AND ((pembayaran.gross_amount = 0) or (pembayaran.jenis_pembayaran='pg' and pembayaran.status_code<>'200')))";
        $deleted = DB::delete($sql, [$barang_id,$id_pembeli]);
        
        $penjualan = DB::table('penjualan')
            ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
            ->select('penjualan.id')
            ->where('penjualan.pembeli_id', $id_pembeli)
            ->where(function($query) {
                $query->where('pembayaran.gross_amount', 0)
                      ->orWhere(function($q) {
                          $q->where('pembayaran.status_code', '!=', 200)
                            ->where('pembayaran.jenis_pembayaran', 'pg');
                      });
            })
            ->first();

        // Update total tagihan pada tabel penjualan
        $tagihan = DB::table('penjualan')
        ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
        ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
        ->select(DB::raw('SUM(harga_jual * jml) as total'))
        ->where('penjualan.pembeli_id', '=', $id_pembeli) 
        ->where(function($query) {
            $query->where('pembayaran.gross_amount', 0)
                  ->orWhere(function($q) {
                      $q->where('pembayaran.status_code', '!=', 200)
                        ->where('pembayaran.jenis_pembayaran', 'pg');
                  });
        })
        ->first();

        if ($penjualan) {
            DB::table('penjualan')
                ->where('id', $penjualan->id)
                ->update(['tagihan' => $tagihan->total]);
        }

        $jmlbarangdibeli = DB::table('penjualan')
                            ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                            ->join('pembeli', 'penjualan.pembeli_id', '=', 'pembeli.id')
                            ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                            ->select(DB::raw('COUNT(DISTINCT barang_id) as total'))
                            ->where('penjualan.pembeli_id', '=', $id_pembeli) 
                            ->where(function($query) {
                                $query->where('pembayaran.gross_amount', 0)
                                      ->orWhere(function($q) {
                                          $q->where('pembayaran.status_code', '!=', 200)
                                            ->where('pembayaran.jenis_pembayaran', 'pg');
                                      });
                            })
                            ->get();


        return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus', 'total' => $tagihan->total, 'jmlbarangdibeli'=>$jmlbarangdibeli[0]->total ?? 0]);
    }

    // untuk autorefresh dari server midtrans yang sudah terbayarkan akan diupdatekan ke database
    // termasuk menangani ketika sudah expired
    public function cek_status_pembayaran_pg(){
        
        $pembayaranPending = Pembayaran::where('jenis_pembayaran', 'pg')
        ->where(DB::raw("IFNULL(status_code, '0')"), '<>', '200')
        ->orderBy('tgl_bayar', 'desc')
        ->get();    
        // var_dump($pembayaranPending);
        // dd();
        $id = array();
        $kode_faktur = array();
		foreach($pembayaranPending as $ks){
			array_push($id,$ks->order_id);
            // echo $ks->order_id;
            
            // untuk mendapatkan no_faktur dari pola F-0000002-20250406 => F-0000002
            $parts = explode('-', $ks->order_id);
            
            $substring = $parts[0] . '-' . $parts[1];
            // dd($substring);
            array_push($kode_faktur,$substring);
            // array_push($kode_faktur,$ks->order_id);
            // echo $substring;
		}

        for($i=0; $i<count($id); $i++){
            // echo "masuk sini";
            $ch = curl_init(); 
            $login = env('MIDTRANS_SERVER_KEY');
            $password = '';
            $orderid = $id[$i];
            // echo $orderid;
            // dd($orderid);
            $kode_faktur = $kode_faktur[$i];
            $URL =  'https://api.sandbox.midtrans.com/v2/'.$orderid.'/status';
            // dd($URL);
            curl_setopt($ch, CURLOPT_URL, $URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");  
            $output = curl_exec($ch); 
            curl_close($ch);    
            $outputjson = json_decode($output, true); //parsing json dalam bentuk assosiative array
            // var_dump($outputjson);
            // dd();

            // lakukan penanganan jika sudah expired
            if($outputjson['status_code']!=404){
                //diluar 404
                if(in_array($outputjson['transaction_status'], ['expire', 'cancel', 'deny'])){
                    // maka kembalikan posisi ke pemesanan 
                    // hapus snap token dari transaction_id
                    $affected = DB::update(
                        'update pembayaran 
                         set status_code = null,
                             transaction_time = null,
                             gross_amount = 0,
                             transaction_id = null
                         where order_id = ?',
                        [
                            $orderid
                        ]
                    );
    
                }else{
                    // 
                    $affected = DB::update(
                        'update pembayaran 
                         set status_code = ?,
                             transaction_time = ?,
                             settlement_time = ?,
                             status_message = ?,
                             merchant_id = ?
                         where order_id = ?',
                        [
                            $outputjson['status_code'] ?? null, 
                            $outputjson['transaction_time'] ?? null, 
                            $outputjson['settlement_time'] ?? null, 
                            $outputjson['status_message'] ?? null, 
                            $outputjson['merchant_id'] ?? null, 
                            $orderid
                        ]
                    );
        
                    if($outputjson['status_code']=='200'){
                        $affected = DB::update(
                            'update penjualan 
                             set status = "bayar"
                             where no_faktur = ?',
                            [
                                $kode_faktur
                            ]
                        );
                    }
                    // 
                }
                // akhir
            }

            // jika tidak ditemukan
            if($outputjson['status_code']==404){
                // cek apakah ada datanya di pembayaran, jika ada maka hapus
                $dataorderid = Pembayaran::where('order_id',$orderid)
                ->select(DB::raw('order_id'))
                ->first();
                if(isset($dataorderid->order_id)){
                    // jika ditemukan maka kembalikan ke awal
                    $affected = DB::update(
                        'update pembayaran 
                         set status_code = null,
                             transaction_time = null,
                             gross_amount = 0,
                             transaction_id = null,
                             order_id = null
                         where order_id = ?',
                        [
                            $orderid
                        ]
                    );
                }
            }
            
            
        }
        return view('autorefresh');
    }

    // melihat riwayat pesanan
    public function lihatriwayat(){
        
        $id_user = Auth::user()->id;

        // dapatkan id_pembeli dari user_id di tabel users sesuai data yang login
        $pembeli = Pembeli::where('user_id', $id_user)
                        ->select(DB::raw('id'))
                        ->first();
        $id_pembeli = $pembeli->id;

        // dd(var_dump($barangdibeli));
        // jumlah barang dibeli
        $jmlbarangdibeli = DB::table('penjualan')
                            ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                            ->join('pembeli', 'penjualan.pembeli_id', '=', 'pembeli.id')
                            ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                            ->select(DB::raw('COUNT(DISTINCT barang_id) as total'))
                            ->where('penjualan.pembeli_id', '=', $id_pembeli) 
                            ->where(function($query) {
                                $query->where('pembayaran.gross_amount', 0)
                                      ->orWhere(function($q) {
                                          $q->where('pembayaran.status_code', '!=', 200)
                                            ->where('pembayaran.jenis_pembayaran', 'pg');
                                      });
                            })
                            ->get();

        $t = DB::table('penjualan')
        ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
        ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
        ->select(DB::raw('SUM(harga_jual * jml) as total'))
        ->where('penjualan.pembeli_id', '=', $id_pembeli) 
        ->where(function($query) {
            $query->where('pembayaran.gross_amount', 0)
                  ->orWhere(function($q) {
                      $q->where('pembayaran.status_code', '!=', 200)
                        ->where('pembayaran.jenis_pembayaran', 'pg');
                  });
        })
        ->first();

        $barang = DB::table('penjualan')
                        ->join('penjualan_barang', 'penjualan.id', '=', 'penjualan_barang.penjualan_id')
                        ->join('pembayaran', 'penjualan.id', '=', 'pembayaran.penjualan_id')
                        ->join('barang', 'penjualan_barang.barang_id', '=', 'barang.id')
                        ->join('pembeli', 'penjualan.pembeli_id', '=', 'pembeli.id')
                        ->select('penjualan.id','penjualan.no_faktur','pembeli.nama_pembeli', 'penjualan_barang.barang_id', 'barang.nama_barang','penjualan_barang.harga_jual', 
                                 'barang.foto',
                                  DB::raw('SUM(penjualan_barang.jml) as total_barang'),
                                  DB::raw('SUM(penjualan_barang.harga_jual * penjualan_barang.jml) as total_belanja'))
                        ->where('penjualan.pembeli_id', '=',$id_pembeli) 
                        ->where(function($query) {
                            $query->where('pembayaran.gross_amount', 0)
                                  ->orWhere(function($q) {
                                      $q->where('pembayaran.status_code', '!=', 200)
                                        ->where('pembayaran.jenis_pembayaran', 'pg');
                                  });
                        })
                        ->groupBy('penjualan.id','penjualan.no_faktur','pembeli.nama_pembeli','penjualan_barang.barang_id', 'barang.nama_barang','penjualan_barang.harga_jual',
                                  'barang.foto',
                                 )
                        ->get();

        // hitung jumlah total tagihan
        $ttl = 0; $jml_brg = 0; $kode_faktur = '';
        foreach($barang as $p){
            $ttl += $p->total_belanja;
            $jml_brg += 1;
            $kode_faktur = $p->no_faktur;
            $idpenjualan = $p->id;
        }
        
        // DATA RIWAYAT PEMESANAN
        $transaksi = DB::select("
                              SELECT * FROM penjualan
                              WHERE pembeli_id = ?
                    ", [$id_pembeli]);

        // Ambil semua id penjualan
        $penjualan_ids = array_column($transaksi, 'id');

        // Ambil detail barang sekaligus
        $detail_barang = DB::table('penjualan_barang')
            ->join('barang', 'penjualan_barang.barang_id', '=', 'barang.id')
            ->whereIn('penjualan_id', $penjualan_ids)
            ->get()
            ->groupBy('penjualan_id'); // dikelompokkan per faktur            
       
        return view('riwayat',
                        [ 
                            'transaksi' => $transaksi,
                            'detail_barang' => $detail_barang ,
                            'total_tagihan' => $ttl,
                            'total_belanja' => $t->total ?? 0,
                            'jmlbarangdibeli' => $jmlbarangdibeli[0]->total ?? 0
                        ]
                    ); 
    }

    // Method baru untuk menangani Webhook Midtrans
    public function handleCallback(Request $request)
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');
        
        // 1. Hitung Signature Key untuk Keamanan
        // Format: SHA512(order_id + status_code + gross_amount + server_key)
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        // 2. Validasi apakah data benar-benar dari Midtrans
        if ($hashed !== $request->signature_key) {
            Log::warning('Midtrans Callback: Signature Key tidak valid untuk Order ID: ' . $request->order_id);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 3. Ambil data penting dari request
        $order_id = $request->order_id;
        // 3.1. Ambil order_id dari request Midtrans
        $order_id_midtrans = $request->order_id; // Contoh: "F-0000002-20260307102517"
        // 3.2. Potong string untuk mendapatkan No Faktur asli
        $no_faktur = Str::beforeLast($order_id_midtrans, '-'); // Hasil: "F-0000002"
        
        
        $status = $request->transaction_status;
        $status_code = $request->status_code;
        $transaction_time = $request->transaction_time;

        // 4. Update data di tabel Pembayaran
        try {
            DB::beginTransaction();

            // Cari data pembayaran berdasarkan order_id
            $pembayaran = DB::table('pembayaran')->where('order_id', $order_id)->first();

            if ($pembayaran) {
                $updateData = [
                    'status_code'    => $status_code,
                    'transaction_time' => $transaction_time,
                    'status_message' => $request->status_message,
                    'updated_at'     => now(),
                ];

                // Jika status lunas (settlement atau capture untuk kartu kredit)
                if ($status == 'settlement' || $status == 'capture') {
                    $updateData['tgl_bayar'] = now();
                    
                    // 3. Update tabel penjualan
                    $updatePenjualan = DB::table('penjualan')
                        ->where('no_faktur', $no_faktur)
                        ->update([
                            'status'     => 'bayar', // Ubah dari 'pesan' menjadi 'bayar'
                            'tgl'        => now(),   // Update ke waktu pembayaran saat ini
                            'updated_at' => now()
                        ]);
                }

                DB::table('pembayaran')
                    ->where('order_id', $order_id)
                    ->update($updateData);

                DB::commit();

                // tambahkan notifikasi WA bahwa pembayaran sudah diterima
                // Query JOIN untuk mendapatkan detail pembeli
                $data_pembeli = DB::table('penjualan')
                    ->join('pembeli', 'penjualan.pembeli_id', '=', 'pembeli.id')
                    ->select('pembeli.nama_pembeli', 'pembeli.telepon', 'penjualan.no_faktur', 'penjualan.tagihan')
                    ->where('penjualan.no_faktur', $no_faktur)
                    ->first();

                $nama = $data_pembeli->nama_pembeli;
                $telepon = $data_pembeli->telepon;
                $jmlbayar = rupiah($data_pembeli->tagihan);

                // 4. Susun Pesan
                $pesan = "Terima kasih Bpk/Ibu *{$nama}*,\n\n" .
                 "Pembayaran untuk Faktur {$no_faktur} sebesar {$jmlbayar} telah kami terima.\n\n" .
                 "Salam dari Management Toko Mukena.";

                $wa = app(NotificationController::class);
                $wa->sendMessage('0'.$telepon, $pesan);

                Log::info('Midtrans Callback Success: Order ' . $order_id . ' status is ' . $status);
                return response()->json(['message' => 'Callback handled successfully']);
            }

            return response()->json(['message' => 'Order not found'], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}