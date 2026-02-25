<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>Scan QR Code</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
	$username = $username ?? (auth()->user()->username ?? auth()->user()->name ?? '');
@endphp
<body class="min-h-screen bg-linear-to-br from-indigo-100 to-slate-100 px-2 py-8" style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
	<main class="relative mx-auto mt-8 w-full max-w-125 rounded-[18px] bg-white px-8 pb-8 pt-10 text-center shadow-[0_8px_32px_rgba(31,38,135,0.12)] max-[700px]:mt-4 max-[700px]:max-w-[99vw] max-[700px]:px-2 max-[700px]:py-5">
		<div class="mb-2 text-[2.5rem] text-blue-600">
			<i class="fa-solid fa-camera"></i>
		</div>
		<h2 class="mb-2 text-[2rem] tracking-[1px] text-blue-900">Scan QR Code</h2>
		<div class="mb-5 text-[0.95rem] text-slate-500">
			Pilih kamera yang tersedia, lalu arahkan ke QR Code absensi.
		</div>

		<div id="reader" class="mx-auto mb-2 mt-6 flex aspect-square w-100 max-w-[98vw] min-w-55 items-center justify-center rounded-xl bg-slate-50 p-4 shadow-[0_2px_8px_rgba(31,38,135,0.07)] max-[700px]:w-[95vw] max-[700px]:max-w-[99vw] max-[700px]:min-w-40 max-[350px]:h-[98vw] max-[350px]:w-[98vw] max-[350px]:min-h-25 max-[350px]:min-w-25"></div>

		<div class="mb-5">
			<label for="camera-select" class="mr-2 text-sm text-slate-700">Pilih Kamera:</label>
			<select id="camera-select" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none"></select>
		</div>

		<a href="{{ url('/dashboard') }}" class="mt-6 inline-block rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
			<i class="fa fa-arrow-left"></i> Kembali ke Dashboard
		</a>
	</main>

	<script>
		const scanUrl = @json(route('scan.process'));
		const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

		let html5QrCode;
		let currentCameraId = null;
		let userLatitude = null;
		let userLongitude = null;

		function getResponsiveQrbox() {
			const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
			let containerSize = 400;
			if (vw < 700) containerSize = Math.floor(vw * 0.95);
			if (vw < 400) containerSize = Math.floor(vw * 0.98);
			if (containerSize < 160) containerSize = 160;

			const qrboxSize = Math.floor(containerSize * 0.8);
			return { width: qrboxSize, height: qrboxSize };
		}

		function getGPSLocation(callback) {
			if (!navigator.geolocation) {
				Swal.fire({
					icon: 'error',
					title: 'GPS Tidak Didukung',
					text: 'Browser Anda tidak mendukung GPS. Gunakan browser modern (Chrome, Firefox, Safari).',
					confirmButtonText: 'OK'
				});
				return;
			}

			Swal.fire({
				title: 'Mengambil Lokasi GPS...',
				html: 'Mohon tunggu sebentar.<br><small>Pastikan GPS aktif dan izinkan akses lokasi.</small>',
				allowOutsideClick: false,
				didOpen: () => {
					Swal.showLoading();
				}
			});

			navigator.geolocation.getCurrentPosition(
				function(position) {
					userLatitude = position.coords.latitude;
					userLongitude = position.coords.longitude;
					Swal.close();
					callback(true);
				},
				function(error) {
					let errorMessage = '';
					switch (error.code) {
						case error.PERMISSION_DENIED:
							errorMessage = 'Akses lokasi ditolak. Silakan izinkan akses lokasi di pengaturan browser.';
							break;
						case error.POSITION_UNAVAILABLE:
							errorMessage = 'Informasi lokasi tidak tersedia. Pastikan GPS aktif.';
							break;
						case error.TIMEOUT:
							errorMessage = 'Waktu permintaan lokasi habis. Coba lagi.';
							break;
						default:
							errorMessage = 'Terjadi kesalahan saat mengambil lokasi.';
					}

					Swal.fire({
						icon: 'error',
						title: 'Lokasi GPS Tidak Terdeteksi',
						html: errorMessage + '<br><br><small>Tips:<br>• Aktifkan GPS di pengaturan<br>• Izinkan akses lokasi<br>• Coba di area outdoor<br>• Nonaktifkan mode hemat baterai</small>',
						confirmButtonText: 'Coba Lagi'
					}).then(() => {
						startCamera(currentCameraId);
					});
					callback(false);
				},
				{
					enableHighAccuracy: true,
					timeout: 10000,
					maximumAge: 0
				}
			);
		}

		function onScanSuccess(decodedText, decodedResult) {
			html5QrCode.stop().then(ignore => {
				getGPSLocation(function(gpsSuccess) {
					if (!gpsSuccess) {
						return;
					}

					Swal.fire({
						title: 'Memproses Absensi...',
						text: 'Mohon tunggu sebentar.',
						allowOutsideClick: false,
						didOpen: () => {
							Swal.showLoading();
						}
					});

					const formData = new FormData();
					formData.append('token', decodedText);
					formData.append('username', @json($username));
					formData.append('latitude', userLatitude);
					formData.append('longitude', userLongitude);

					fetch(scanUrl, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': csrfToken,
							'Accept': 'application/json'
						},
						body: formData
					})
						.then(response => response.json())
						.then(data => {
							if (data.status === 'success') {
								Swal.fire({
									icon: 'success',
									title: 'Absensi Berhasil!',
									text: 'Data absensi Anda telah direkam.',
									timer: 2000,
									showConfirmButton: false
								}).then(() => {
									window.location.href = '{{ url('/dashboard') }}';
								});
							} else {
								Swal.fire({
									icon: 'error',
									title: 'Gagal!',
									text: data.message,
									confirmButtonText: 'Coba Lagi'
								}).then(() => {
									startCamera(currentCameraId);
								});
							}
						})
						.catch(error => {
							console.error('Error:', error);
							Swal.fire({
								icon: 'error',
								title: 'Oops...',
								text: 'Terjadi kesalahan. Periksa koneksi Anda dan coba lagi.',
								confirmButtonText: 'Coba Lagi'
							}).then(() => {
								startCamera(currentCameraId);
							});
						});
				});
			}).catch(err => {
				console.error('Gagal menghentikan pemindai.', err);
			});
		}

		function startCamera(cameraId) {
			const qrbox = getResponsiveQrbox();
			if (html5QrCode && html5QrCode.isScanning) {
				html5QrCode.stop().then(() => {
					html5QrCode.start(cameraId, { fps: 10, qrbox: qrbox }, onScanSuccess, () => {});
				});
			} else {
				html5QrCode = new Html5Qrcode('reader');
				html5QrCode.start(cameraId, { fps: 10, qrbox: qrbox }, onScanSuccess, () => {});
			}
			currentCameraId = cameraId;
		}

		Html5Qrcode.getCameras().then(devices => {
			const select = document.getElementById('camera-select');
			if (devices && devices.length) {
				devices.forEach(device => {
					const option = document.createElement('option');
					option.value = device.id;
					option.text = device.label || `Kamera ${select.length + 1}`;
					select.appendChild(option);
				});
				currentCameraId = devices[1]?.id || devices[0].id;
				startCamera(currentCameraId);
				select.onchange = function() {
					startCamera(this.value);
				};
			}
		}).catch(err => {
			document.getElementById('reader').innerText = 'Kamera tidak tersedia: ' + err;
		});

		window.addEventListener('resize', function() {
			if (currentCameraId && html5QrCode && html5QrCode.isScanning) {
				html5QrCode.stop().then(() => {
					const qrbox = getResponsiveQrbox();
					html5QrCode.start(currentCameraId, { fps: 10, qrbox: qrbox }, onScanSuccess, () => {});
				});
			}
		});
	</script>
</body>
</html>
