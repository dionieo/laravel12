<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>QR Code Absensi</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
	$currentTime = now('Asia/Jakarta')->format('H:i');
	$timeDatangStart = '07:00';
	$timeDatangEnd = '09:00';
	$timePulangStart = '16:00';
	$timePulangEnd = '23:59';

	$sessionMessage = 'Saat ini di luar jam absensi.';

	if ($currentTime >= $timeDatangStart && $currentTime <= $timeDatangEnd) {
		$sessionMessage = "Sesi Absensi: <strong>Datang ($timeDatangStart - $timeDatangEnd)</strong>";
	} elseif ($currentTime >= $timePulangStart && $currentTime <= $timePulangEnd) {
		$sessionMessage = "Sesi Absensi: <strong>Pulang ($timePulangStart - $timePulangEnd)</strong>";
	}
@endphp
<body class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-5 pb-24 pt-5" style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
	<div class="flex w-full max-w-150 flex-1 flex-col items-center justify-center">
		<header class="mb-4 w-full max-w-150 text-center md:mb-6">
			<h1 class="mb-2 flex items-center justify-center gap-2.5 text-[22px] font-bold text-slate-800 sm:text-xl">
				<i data-lucide="qr-code" class="h-7 w-7 text-[#2463EB]"></i>
				QR Code Absensi
			</h1>
			<div class="inline-block rounded-xl border border-blue-300 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-900 [&_strong]:text-[#2463EB]">
				{!! $sessionMessage !!}
			</div>
		</header>

		<section class="mb-5 w-full max-w-150 rounded-3xl bg-white p-5 shadow-[0_4px_16px_rgba(0,0,0,0.08)] sm:p-4 md:max-w-125">
			<div id="qrcode" class="flex aspect-square w-full items-center justify-center rounded-2xl bg-white p-5 sm:p-4">
				<div class="px-5 py-10 text-center text-sm text-slate-500">Memuat QR Code...</div>
			</div>
		</section>

		<section class="mb-6 text-center">
			<p class="mb-1.5 text-base font-semibold text-slate-700">Silakan Scan QR Code Ini</p>
			<p class="text-[13px] leading-relaxed text-slate-500">
				Gunakan aplikasi absensi siswa untuk memindai.<br>
				QR code akan diperbarui otomatis setelah digunakan.
			</p>
		</section>

		<a href="dashboard" class="flex w-full max-w-150 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-[15px] font-bold text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 md:max-w-125">
			<i data-lucide="arrow-left" class="h-5 w-5"></i>
			<span>Kembali ke Dashboard</span>
		</a>
	</div>

	<x-navbar />

	<script>
		const qrContainer = document.getElementById('qrcode');
		let currentToken = '';

		const urlGet      = @json(route('token.get'));
		const urlGenerate = @json(route('token.generate'));
		const urlStatus   = @json(route('token.status'));

		function displayQRCode(token) {
			if (!token) return;
			currentToken = token;
			qrContainer.innerHTML = '';

			setTimeout(() => {
				const containerWidth = qrContainer.offsetWidth;

				if (containerWidth > 0) {
					const size = Math.floor(containerWidth - 40);

					new QRCode(qrContainer, {
						text: token,
						width: size,
						height: size,
						colorDark: '#000000',
						colorLight: '#ffffff',
						correctLevel: QRCode.CorrectLevel.H,
					});
				} else {
					qrContainer.innerHTML = '<div class="px-5 py-10 text-center text-sm text-slate-500">Memuat QR Code...</div>';
				}
			}, 50);
		}

		async function fetchAPI(url) {
			try {
				const response = await fetch(url, {
					headers: {
						'Accept': 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
					},
					credentials: 'same-origin',
				});

				if (!response.ok) {
					throw new Error(`HTTP ${response.status}`);
				}

				return await response.json();
			} catch (error) {
				console.error('Fetch error:', error);
				qrContainer.innerHTML = '<div class="px-5 py-10 text-center text-sm text-red-600">Gagal memuat QR Code.<br>Cek koneksi internet Anda.</div>';
				return null;
			}
		}

		async function generateNewQRCode() {
			const data = await fetchAPI(urlGenerate);
			if (data && data.success && data.token) {
				displayQRCode(data.token);
			}
		}

		async function initializeQRCode() {
			const data = await fetchAPI(urlGet);
			if (data && data.success && data.token && data.token.trim() !== '') {
				displayQRCode(data.token.trim());
			} else {
				await generateNewQRCode();
			}
		}

		setInterval(async () => {
			const data = await fetchAPI(urlStatus);
			if (data && data.status !== 'active') {
				await generateNewQRCode();
			}
		}, 3000);

		window.addEventListener('resize', () => {
			if (currentToken) {
				displayQRCode(currentToken);
			}
		});

		initializeQRCode();
		lucide.createIcons();
	</script>
</body>
</html>
