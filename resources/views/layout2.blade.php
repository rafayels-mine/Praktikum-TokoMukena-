<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts/header')
</head>
<body>
    Selamat Datang {{ $nama }} <hr>

    <!-- masukkan template konten -->
    @yield('konten')
    <hr>

    <!-- Masukkan footer -->
     @include('layouts.footer')
</body>
</html>