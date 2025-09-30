<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

start_app_session();
$user = current_user();
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
	<title>SeniorCare Information System | Official Portal</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
	<div class="login-container">
		<!-- Government Header -->
		<div class="login-header">
			<div class="login-header-content">
				<div class="login-logo">🏛️</div>
				<div>
					<div class="login-title">SeniorCare Information System</div>
					<div class="login-subtitle">Office of Senior Citizens Affairs - Official Portal</div>
				</div>
			</div>
		</div>
		
		<!-- Login Form -->
		<div class="login-form-container">
			<div class="login-form-card">
				<h2>System Access Portal</h2>
				<p class="text-center text-muted mb-6">Enter your credentials to access the system</p>
				
				<?php if ($error): ?>
					<div class="alert alert-danger">
						<strong>⚠️ Access Denied:</strong> <?= htmlspecialchars($error) ?>
					</div>
				<?php endif; ?>
				
				<form method="post" action="<?= BASE_URL ?>/login.php" class="login-form">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					
					<div class="form-group">
						<label for="email" class="form-label">📧 Username / Email Address</label>
						<input type="email" id="email" name="email" class="form-input" required placeholder="Enter your official email address">
					</div>
					
					<div class="form-group">
						<label for="password" class="form-label">🔒 Password</label>
						<input type="password" id="password" name="password" class="form-input" required placeholder="Enter your secure password">
					</div>
					
					<div class="form-group">
						<button type="submit" class="btn btn-primary btn-lg btn-full">
							🚀 Access System
						</button>
					</div>
					
					<div class="text-center">
						<a href="#" onclick="alert('Contact system administrator for password reset')" class="text-muted">
							🔑 Forgot Password?
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
		// Password toggle functionality
		function togglePassword() {
			const passwordInput = document.getElementById('password');
			const passwordIcon = document.getElementById('password-icon');
			
			if (passwordInput.type === 'password') {
				passwordInput.type = 'text';
				passwordIcon.classList.remove('fa-eye');
				passwordIcon.classList.add('fa-eye-slash');
			} else {
				passwordInput.type = 'password';
				passwordIcon.classList.remove('fa-eye-slash');
				passwordIcon.classList.add('fa-eye');
			}
		}

		// Form submission with loading state
		document.querySelector('.auth-form').addEventListener('submit', function(e) {
			const button = this.querySelector('.auth-button');
			const buttonText = button.querySelector('.button-text');
			const buttonLoading = button.querySelector('.button-loading');
			
			button.classList.add('loading');
			buttonText.style.opacity = '0';
			buttonLoading.style.opacity = '1';
		});

		// Input focus animations
		document.querySelectorAll('.form-input').forEach(input => {
			input.addEventListener('focus', function() {
				this.parentNode.classList.add('focused');
			});
			
			input.addEventListener('blur', function() {
				if (!this.value) {
					this.parentNode.classList.remove('focused');
				}
			});
		});

		// Floating shapes animation
		function animateShapes() {
			const shapes = document.querySelectorAll('.shape');
			shapes.forEach((shape, index) => {
				const delay = index * 0.5;
				const duration = 3 + Math.random() * 2;
				shape.style.animation = `float ${duration}s ease-in-out infinite`;
				shape.style.animationDelay = `${delay}s`;
			});
		}

		// Initialize animations
		document.addEventListener('DOMContentLoaded', function() {
			animateShapes();
			
			// Add entrance animation to auth card
			const authCard = document.querySelector('.auth-card');
			setTimeout(() => {
				authCard.classList.add('animate-fade-in');
			}, 100);
		});
	</script>
</body>
</html>


