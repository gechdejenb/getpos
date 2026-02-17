<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <!-- PWA  -->
<meta name="theme-color" content="#6777ef"/>
<link rel="apple-touch-icon" href="{{ asset('logo.PNG') }}">
<link rel="manifest" href="{{ secure_asset('/manifest.json') }}">
    <link rel=icon href=/images/favicon.ico>
    <link rel="stylesheet" href="/css/master.css">

    <title>Getpos inventory</title>
  </head>

  <body class="text-left">
    <noscript>
      <strong>
        We're sorry but the system doesn't work properly without JavaScript
        enabled. Please enable it to continue.</strong
      >
    </noscript>

    <!-- built files will be auto injected -->
    <div class="loading_wrap" id="loading_wrap">
      <div class="loader_logo">
      <img src="/images/logo.png" class="" alt="logo" />

      </div>

      <div class="loading"></div>
    </div>
    <div id="app">
    </div>
    <script src="{{ secure_asset('/sw.js') }}"></script>
    <script>
      if ("serviceWorker" in navigator) {
          // Register a service worker hosted at the root of the
          // site using the default scope.
          navigator.serviceWorker.register(window.location.origin + "/sw.js",{ scope: '/' }).then(
          (registration) => {
            console.log("Service worker registration succeeded:", registration);
          },
          (error) => {
            console.error(`Service worker registration failed: ${error}`);
          },
        );
      } else {
        console.error("Service workers are not supported.");
      }
    </script>
    <script src="/js/main.min.js?v=4.0.4"></script>
  </body>
</html>
