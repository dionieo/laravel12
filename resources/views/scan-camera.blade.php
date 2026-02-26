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
	<style>
		/* Tame html5-qrcode internal elements on mobile */
		#reader {
			border: none !important;
		}
		#reader video {
			width: 100% !important;
			height: auto !important;
			border-radius: 0.75rem;
			object-fit: cover;
		}
		#reader__scan_region {
			min-height: 0 !important;
		}
		#reader__scan_region img {
			display: none !important;
		}
		#reader__dashboard_section {
			display: none !important;
		}
		#reader__header_message {
			display: none !important;
		}
		#qr-shaded-region {
			border-width: 2px !important;
		}
	</style>
</head>
@php
	$username = $username ?? (auth()->user()->username ?? auth()->user()->name ?? '');
@endphp
<body class="min-h-screen bg-linear-to-br from-indigo-100 to-slate-100 px-3 py-4 sm:px-4 sm:py-8" style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
	<main class="relative mx-auto w-full max-w-md rounded-2xl bg-white px-4 pb-6 pt-6 text-center shadow-lg sm:mt-4 sm:px-8 sm:pb-8 sm:pt-10">
		<div class="mb-1.5 text-3xl text-blue-600 sm:mb-2 sm:text-4xl">
			<i class="fa-solid fa-camera"></i>
		</div>
		<h2 class="mb-1.5 text-xl font-bold tracking-wide text-blue-900 sm:mb-2 sm:text-2xl">Scan QR Code</h2>
		<p class="mb-4 text-xs text-slate-500 sm:mb-5 sm:text-sm">
			Arahkan kamera ke QR Code absensi.
		</p>

		<div id="reader" class="mx-auto mb-3 w-full overflow-hidden rounded-xl bg-slate-50 shadow-sm sm:mb-4"></div>

		<div class="mb-4 flex items-center justify-center gap-2 sm:mb-5">
			<label for="camera-select" class="text-xs font-medium text-slate-600 sm:text-sm">Kamera:</label>
			<select id="camera-select" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none sm:text-sm"></select>
		</div>

		<a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-blue-700 active:bg-blue-800 sm:text-sm">
			<i class="fa fa-arrow-left text-xs"></i> Kembali ke Dashboard
		</a>
	</main>

	<script>
		const scanUrl = '/scan';
		const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

		let html5QrCode;
		let currentCameraId = null;
		let userLatitude = null;
		let userLongitude = null;

		function getResponsiveQrbox() {
			const readerEl = document.getElementById('reader');
			const containerWidth = readerEl ? readerEl.offsetWidth : 300;
			const qrboxSize = Math.floor(containerWidth * 0.7);
			return { width: Math.max(qrboxSize, 150), height: Math.max(qrboxSize, 150) };
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
