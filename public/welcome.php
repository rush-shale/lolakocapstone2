<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/session.php';

// Check if user is logged in
if (!current_user()) {
	header('Location: ' . BASE_URL . '/index.php');
	exit;
}

$user = current_user();
$target = $user['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Welcome | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css" />
	<style>
		.welcome-container {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
			display: flex;
			align-items: center;
			justify-content: center;
			z-index: 9999;
		}
		
		.welcome-content {
			text-align: center;
			color: white;
			animation: welcomeFadeIn 1.5s ease-out;
		}
		
		.welcome-logo {
			width: 120px;
			height: 120px;
			margin: 0 auto 2rem;
			background: white;
			border-radius: 20px;
			padding: 20px;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
			animation: logoFloat 2s ease-in-out infinite;
		}
		
		.welcome-logo img {
			width: 100%;
			height: 100%;
			object-fit: contain;
		}
		
		.welcome-title {
			font-size: 2.5rem;
			font-weight: 700;
			margin-bottom: 1rem;
			text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
		}
		
		.welcome-subtitle {
			font-size: 1.2rem;
			margin-bottom: 2rem;
			opacity: 0.9;
		}
		
		.welcome-user {
			font-size: 1.1rem;
			margin-bottom: 3rem;
			opacity: 0.8;
		}
		
		.loading-spinner {
			width: 40px;
			height: 40px;
			border: 4px solid rgba(255, 255, 255, 0.3);
			border-top: 4px solid white;
			border-radius: 50%;
			animation: spin 1s linear infinite;
			margin: 0 auto;
		}
		
		@keyframes welcomeFadeIn {
			from {
				opacity: 0;
				transform: translateY(30px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
		
		@keyframes logoFloat {
			0%, 100% {
				transform: translateY(0);
			}
			50% {
				transform: translateY(-10px);
			}
		}
		
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
	</style>
</head>
<body>
	<div class="welcome-container">
		<div class="welcome-content">
			<div class="welcome-logo">
				<img src="<?= BASE_URL ?>/images/OSCA LOGO.png" alt="OSCA Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
				<div style="display: none; font-size: 4rem;">🏛️</div>
			</div>
			
			<h1 class="welcome-title">Welcome to SeniorCare</h1>
			<p class="welcome-subtitle">Office of Senior Citizens Affairs</p>
			<p class="welcome-user">Hello, <?= htmlspecialchars($user['name']) ?>!</p>
			
			<div class="loading-spinner"></div>
		</div>
	</div>
	
	<script>
		// Redirect after 3 seconds
		setTimeout(() => {
			window.location.href = '<?= BASE_URL . $target ?>';
		}, 3000);
	</script>
</body>
</html>
