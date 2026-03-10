<!DOCTYPE html>
<html>

<head>
  <base href="/">
  <meta charset="UTF-8">
  <meta content="IE=Edge" http-equiv="X-UA-Compatible">
  <meta name="description" content="RV CRM Mobile - CRM for your business">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <meta name="apple-mobile-web-app-title" content="RV CRM">
  <link rel="apple-touch-icon" href="icons/Icon-192.png">
  <link rel="icon" type="image/svg+xml"
    href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23f59e0b' /%3E%3Cstop offset='100%25' stop-color='%2310b981' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='25' fill='url(%23g)' /%3E%3Ctext x='50' y='68' font-family='Arial, sans-serif' font-weight='900' font-size='52' fill='white' text-anchor='middle'%3ERV%3C/text%3E%3C/svg%3E">
  <title>RV CRM</title>
  <link rel="manifest" href="manifest.json">
  <script>
    const serviceWorkerVersion = "2601558134";
    var scriptLoaded = false;
    function loadMainDartJs() {
      if (scriptLoaded) return;
      scriptLoaded = true;
      var scriptTag = document.createElement('script');
      scriptTag.src = 'main.dart.js';
      scriptTag.type = 'application/javascript';
      document.body.append(scriptTag);
    }

    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('flutter_service_worker.js?v=' + serviceWorkerVersion)
          .then(function (registration) {
            loadMainDartJs();
          });
        setTimeout(loadMainDartJs, 4000);
      });
    } else {
      loadMainDartJs();
    }
  </script>
</head>

<body>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f8fafc;
    }

    .loading {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid #e2e8f0;
      border-top: 4px solid #6366f1;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    .loading-text {
      margin-top: 20px;
      color: #64748b;
      font-size: 16px;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }
  </style>
  <div class="loading">
    <div class="loading-spinner"></div>
    <div class="loading-text">Loading RV CRM...</div>
  </div>
</body>

</html>