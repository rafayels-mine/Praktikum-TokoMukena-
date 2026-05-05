<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="text/javascript"
      src="https://app.sandbox.midtrans.com/snap/snap.js"
      data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
    
    <style>
        .container { margin: 20px; font-family: sans-serif; text-align: center; }
        .btn-pay { padding: 12px 24px; background: #2ecc71; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold; }
        .btn-simulasi { padding: 12px 24px; background: #3498db; color: white; border: none; cursor: pointer; margin-left: 10px; border-radius: 5px; font-weight: bold; }
        .log-box { margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; text-align: left; display: inline-block; min-width: 300px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.9em; }
    </style>
  </head>
 
  <body>
    <div class="container">
        <h2>Nano Banana Midtrans Lab 🍌</h2>
        <p>Order ID: <strong>{{ $order_id }}</strong></p>
        
        <button id="pay-button" class="btn-pay">Pay with Snap!</button>

        <button id="simulate-callback" class="btn-simulasi">Simulasi Webhook (Lunas)</button>

        <div id="status-log" class="log-box" style="display:none;">
            <strong>Server Response:</strong><br>
            <code id="log-message" style="word-break: break-all;"></code>
        </div>
    </div>

    <script type="text/javascript">
      // 1. LOGIKA SNAP (POPUP MIDTRANS)
      var payButton = document.getElementById('pay-button');
      payButton.addEventListener('click', function () {
        window.snap.pay('{{ $snap_token }}', {
            onSuccess: function(result){ alert("Snap Success!"); console.log(result); },
            onPending: function(result){ alert("Snap Pending!"); console.log(result); },
            onError: function(result){ alert("Snap Error!"); console.log(result); }
        });
      });

      // 2. LOGIKA SIMULASI CALLBACK (KE CONTROLLER)
      var simButton = document.getElementById('simulate-callback');
      simButton.addEventListener('click', function () {
        const logBox = document.getElementById('status-log');
        const logMsg = document.getElementById('log-message');
        
        logBox.style.display = 'block';
        logMsg.innerText = "Processing webhook simulation...";
        logMsg.style.color = "black";

        fetch('/api/midtrans-callback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                order_id: '{{ $order_id }}',
                status_code: "200",
                gross_amount: '{{ $gross_amount }}', // Disesuaikan dengan variabel controller
                transaction_status: "settlement",
                payment_type: "bank_transfer",
                transaction_id: "sim-uuid-" + Math.random().toString(36).substring(7),
                signature_key: '{{ $signature }}' // MENGGUNAKAN SIGNATURE ASLI DARI CONTROLLER
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('HTTP error ' + response.status);
            return response.json();
        })
        .then(data => {
            logMsg.innerText = JSON.stringify(data, null, 2);
            logMsg.style.color = "#2980b9";
            alert("Simulasi Berhasil! Cek database tabel payment_logs.");
        })
        .catch(error => {
            logMsg.innerText = "Error: " + error.message;
            logMsg.style.color = "#e74c3c";
        });
      });
    </script>
  </body>
</html>