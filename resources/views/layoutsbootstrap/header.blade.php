<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Toko Mukena</title>
  <link rel="shortcut icon" type="image/png" href="{{asset('images/logos/mukena.png')}}" />
  <link rel="stylesheet" href="{{asset('css/styles.min.css')}}" />

  <style>
    /* Optional: Custom CSS for DataTables */
    table.dataTable thead tr {
      background-color: LightGray;
    }
    table.dataTable tfoot tr {
      background-color: LightGray;
    }
  </style>

  <!-- Include ApexCharts library -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  <!-- Untuk Tambahan DataTables -->
  <link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">

  <!-- Bootstrap core JavaScript-->
  <script src="{{asset('vendor/jquery/jquery.min.js')}}"></script>
  <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  
  <!-- Untuk sweet alert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Untuk select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>



  <!-- Tambahan form validation pop up -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

</head>