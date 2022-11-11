function serialize(obj) {
  let result = '';
  if (typeof obj === 'object' && !Array.isArray(obj)) {
    for (let prop in obj) {
      result += `${prop}=${obj[prop]}&`;
    }
  }
  return trimFilter(result);
}

/**
 * Trim string as filter string.
 * @param {string} str String to filter.
 * @example
 * // Return '1,2,3,4'
 * trimFilter('1,2,3,4, ');
 */
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
  navigator.geolocation.watchPosition(function (pos) {
    data = {
      cmd: 'set',
      lat: pos.coords.latitude,
      lon: pos.coords.longitude
    };

    let xmlhttp = new XMLHttpRequest();
    xmlhttp.addEventListener('load', function (ev) {

    });
    xmlhttp.open('POST', site.base_url + 'api/v1/geolocation');
    xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xmlhttp.send(serialize(data));
  }, function (error) {
    if (error.PERMISSION_DENIED) {
      console.warn('Geolocation require permission.');
      //if (user_id == 1) alertify.alert('Geolocation Access', 'Dimohon untuk mengizinkan akses lokasi kepada PrintERP. Terima kasih.');
    }
  });
}

if (navigator.mediaDevices) {
  navigator.mediaDevices.getUserMedia({
    video: true
  }).then((stream) => {
    // document.querySelector('video').srcObject = stream;
    document.getElementById('camera').srcObject = stream;
  });
}