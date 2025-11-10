<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

start_app_session();
$user = current_user();
// Lightweight CSRF token fetch for login form (avoids stale tokens)
if (isset($_GET['action']) && $_GET['action'] === 'csrf') {
	$token = $_SESSION[CSRF_TOKEN_NAME] ?? null;
	if (!$token) {
		$token = generate_csrf_token();
	}
	header('Content-Type: application/json');
	echo json_encode(['csrf' => $token]);
	exit;
}
if ($user) {
	if ($user['role'] === 'admin') {
		header('Location: ' . BASE_URL . '/admin/dashboard.php');
		exit;
	} else {
		header('Location: ' . BASE_URL . '/user/dashboard.php');
		exit;
	}
}

$error = $_GET['error'] ?? '';
$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1e88e5">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<meta name="apple-mobile-web-app-title" content="OSCA MANOLO">
	<title>OSCA MANOLO - SeniorCare Information System</title>
	<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
	<link rel="apple-touch-icon" href="<?= BASE_URL ?>/images/OSCA MAIN LOGO.png">
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
	<div class="login-container">
		<!-- Government Header -->
		<div class="login-header">
			<div class="login-header-content">
				<div class="login-logo">
					<img src="<?= BASE_URL ?>/images/OSCA MAIN LOGO.png" alt="OSCA Logo" class="login-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
					<div class="login-logo-fallback" style="display: none;">🏛️</div>
				</div>
				<div>
					<div class="login-title">SeniorCare Information System</div>
					<div class="login-subtitle">Office of Senior Citizens Affairs - Manolo Fortich</div>
				</div>
			</div>
		</div>
		
		<!-- Login Form -->
		<div class="login-form-container">
			<div class="login-form-card">
				<div class="login-form-header">
					<h2>Welcome Back</h2>
					<p class="login-form-subtitle">Sign in to access your dashboard</p>
				</div>
				
				<?php if ($error): ?>
					<div class="alert alert-danger modern-alert">
						<div class="alert-icon">⚠️</div>
						<div class="alert-content">
							<strong>Access Denied</strong>
							<p><?= htmlspecialchars($error) ?></p>
						</div>
					</div>
				<?php endif; ?>
				
				<form method="post" action="<?= BASE_URL ?>/login.php" class="login-form modern-form" novalidate>
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					
					<div class="form-group modern-form-group">
						<label for="email" class="form-label modern-label">
							<span class="label-icon">📧</span>
							<span class="label-text">Email Address</span>
						</label>
						<input type="email" id="email" name="email" class="form-input modern-input" required placeholder="Enter your email address" autocomplete="email">
						<div class="input-focus-border"></div>
					</div>
					
					<div class="form-group modern-form-group">
						<label for="password" class="form-label modern-label">
							<span class="label-icon">🔒</span>
							<span class="label-text">Password</span>
						</label>
						<div class="password-input-wrapper">
							<input type="password" id="password" name="password" class="form-input modern-input" required placeholder="Enter your password" autocomplete="current-password">
							<button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
								<span class="password-icon">👁️</span>
							</button>
						</div>
						<div class="input-focus-border"></div>
					</div>
					
					<div class="form-group">
						<button type="submit" class="btn btn-primary btn-lg btn-full modern-btn">
							<span class="btn-text">Sign In</span>
							<span class="btn-icon">→</span>
							<div class="btn-loading">
								<div class="loading-spinner"></div>
								<span>Signing in...</span>
							</div>
						</button>
					</div>
					
					<div class="login-footer">
						<a href="#" onclick="showPasswordReset()" class="forgot-password-link">
							<span class="link-icon">🔑</span>
							<span>Forgot your password?</span>
						</a>
					</div>
				</form>
			</div>
		</div>
		
		<!-- Government Footer -->
		<div class="gov-footer">
			<div class="gov-footer-content">
				<p>&copy; 2025 Department of Social Services - Republic of the Philippines</p>
				<p>SeniorCare Information System - Official Government Portal</p>
				<div class="gov-footer-links">
					<a href="#" onclick="alert('Technical Support: (02) 123-4567')">🆘 Technical Support</a>
					<a href="#" onclick="alert('Unauthorized access is punishable under Republic Act 10175')">⚖️ Security Notice</a>
					<a href="#" onclick="alert('Data Privacy Act of 2012 Compliance')">🔒 Privacy Policy</a>
				</div>
			</div>
		</div>
	</div>

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Refresh CSRF to avoid stale token errors
		async function refreshCsrf() {
			try {
				const res = await fetch('<?= BASE_URL ?>/index.php?action=csrf', { credentials: 'same-origin' });
				const data = await res.json();
				if (data && data.csrf) {
					const inp = document.querySelector('input[name=\"csrf\"]');
					if (inp) inp.value = data.csrf;
				}
			} catch (e) {}
		}
		// Enhanced password toggle with better UX
		function togglePassword() {
			const input = document.getElementById('password');
			const icon = document.querySelector('.password-icon');
			if (!input || !icon) return;
			
			const isPassword = input.type === 'password';
			input.type = isPassword ? 'text' : 'password';
			icon.textContent = isPassword ? '🙈' : '👁️';
			
			// Add visual feedback
			const wrapper = input.closest('.password-input-wrapper');
			wrapper.classList.add('password-revealed');
			setTimeout(() => wrapper.classList.remove('password-revealed'), 200);
		}

		// Enhanced form submission with better loading states
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.querySelector('.login-form');
			if (!form) return;
			
			form.addEventListener('submit', function(e) {
				// last-moment CSRF refresh
				e.preventDefault();
				refreshCsrf().then(() => {
					form.submit();
				}).catch(() => form.submit());
				const btn = form.querySelector('button[type="submit"]');
				if (btn) {
					btn.classList.add('loading');
					btn.disabled = true;
					
					// Add loading animation
					const btnText = btn.querySelector('.btn-text');
					const btnLoading = btn.querySelector('.btn-loading');
					if (btnText && btnLoading) {
						btnText.style.display = 'none';
						btnLoading.style.display = 'flex';
					}
				}
			});
			
			// Enhanced input focus animations
			document.querySelectorAll('.modern-input').forEach(function(input) {
				input.addEventListener('focus', function() {
					const group = this.closest('.modern-form-group');
					if (group) {
						group.classList.add('focused');
					}
				});
				
				input.addEventListener('blur', function() {
					const group = this.closest('.modern-form-group');
					if (group && !this.value) {
						group.classList.remove('focused');
					}
				});
				
				// Check if input has value on load
				if (input.value) {
					const group = input.closest('.modern-form-group');
					if (group) {
						group.classList.add('focused');
					}
				}
			});
			
			// Add floating label effect
			document.querySelectorAll('.modern-label').forEach(function(label) {
				const input = label.nextElementSibling || label.parentNode.querySelector('input');
				if (input && input.value) {
					label.classList.add('floating');
				}
			});
		});

		// Password reset modal
		function showPasswordReset() {
			const modal = document.createElement('div');
			modal.className = 'password-reset-modal';
			modal.innerHTML = `
				<div class="modal-overlay" onclick="closePasswordReset()">
					<div class="modal-content" onclick="event.stopPropagation()">
						<div class="modal-header">
							<h3>Reset Password</h3>
							<button onclick="closePasswordReset()" class="modal-close">&times;</button>
						</div>
						<div class="modal-body">
							<p>To reset your password, please contact your system administrator:</p>
							<div class="contact-info">
								<div class="contact-item">
									<span class="contact-icon">📞</span>
									<span>Phone: (088) 123-4567</span>
								</div>
								<div class="contact-item">
									<span class="contact-icon">📧</span>
									<span>Email: admin@manolofortich.gov.ph</span>
								</div>
								<div class="contact-item">
									<span class="contact-icon">🏢</span>
									<span>Office: Municipal Hall, Manolo Fortich</span>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button onclick="closePasswordReset()" class="btn btn-secondary">Close</button>
						</div>
					</div>
				</div>
			`;
			document.body.appendChild(modal);
		}

		function closePasswordReset() {
			const modal = document.querySelector('.password-reset-modal');
			if (modal) {
				modal.remove();
			}
		}
	</script>
	<script>
		// Register Service Worker for PWA
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', () => {
				navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js')
					.then((registration) => {
						console.log('Service Worker registered:', registration);
					})
					.catch((error) => {
						console.log('Service Worker registration failed:', error);
					});
			});
		}
	</script>
</body>
</html>


