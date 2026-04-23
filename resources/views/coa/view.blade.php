@extends('layout2')

@section('konten')
<h1>Data Coa</h1>
<ul>
    @foreach ($coa as $p)
        <li>{{ "Kode Akun : ".$p->kode_akun.' | Nama Akun = '.$p->nama_akun }}</li>
    @endforeach
</ul>
@endsection