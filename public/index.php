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
	<title>LoLaKo | Senior Citizen Management System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth">
	<!-- Animated Background -->
	<div class="auth-background">
		<div class="floating-shapes">
			<div class="shape shape-1"></div>
			<div class="shape shape-2"></div>
			<div class="shape shape-3"></div>
			<div class="shape shape-4"></div>
			<div class="shape shape-5"></div>
		</div>
	</div>

	<!-- Dark Mode Toggle -->
	<div class="theme-toggle-container">
		<button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Dark Mode">
			<span class="theme-icon">🌙</span>
		</button>
	</div>

	<!-- Main Login Card -->
	<div class="auth-container">
		<div class="auth-card">
			<!-- Header Section -->
			<div class="auth-header">
				<div class="auth-logo">
					<div class="logo-icon">
						<i class="fas fa-building"></i>
					</div>
					<h1>LoLaKo</h1>
					<p class="auth-subtitle">Senior Citizen Management System</p>
				</div>
			</div>

			<!-- Error Alert -->
			<?php if ($error): ?>
				<div class="alert alert-error animate-slide-in">
					<div class="alert-icon">
						<i class="fas fa-exclamation-triangle"></i>
					</div>
					<div class="alert-content">
						<strong>Login Failed</strong>
						<p><?= htmlspecialchars($error) ?></p>
					</div>
				</div>
			<?php endif; ?>

			<!-- Login Form -->
			<form method="post" action="<?= BASE_URL ?>/login.php" class="auth-form">
				<input type="hidden" name="csrf" value="<?= $csrf ?>">
				
				<div class="form-group">
					<label for="email" class="form-label">
						<i class="fas fa-envelope"></i>
						Email Address
					</label>
					<input 
						type="email" 
						name="email" 
						id="email"
						class="form-input" 
						placeholder="Enter your email address"
						required
						autocomplete="email"
					>
					<div class="input-focus-line"></div>
				</div>

				<div class="form-group">
					<label for="password" class="form-label">
						<i class="fas fa-lock"></i>
						Password
					</label>
					<div class="password-input-wrapper">
						<input 
							type="password" 
							name="password" 
							id="password"
							class="form-input" 
							placeholder="Enter your password"
							required
							autocomplete="current-password"
						>
						<button type="button" class="password-toggle" onclick="togglePassword()">
							<i class="fas fa-eye" id="password-icon"></i>
						</button>
					</div>
					<div class="input-focus-line"></div>
				</div>

				<div class="form-options">
					<label class="checkbox-wrapper">
						<input type="checkbox" name="remember" id="remember">
						<span class="checkmark"></span>
						<span class="checkbox-label">Remember me</span>
					</label>
					<a href="#" class="forgot-password">Forgot Password?</a>
				</div>

				<button type="submit" class="auth-button">
					<span class="button-text">Sign In</span>
					<div class="button-loading">
						<i class="fas fa-spinner fa-spin"></i>
					</div>
					<div class="button-ripple"></div>
				</button>
			</form>

			<!-- Footer -->
			<div class="auth-footer">
				<p>&copy; 2024 LoLaKo. All rights reserved.</p>
				<p class="auth-version">Version 2.0.0</p>
			</div>
		</div>

		<!-- Features Preview -->
		<div class="auth-features">
			<div class="feature-card">
				<div class="feature-icon">
					<i class="fas fa-users"></i>
				</div>
				<h3>Senior Management</h3>
				<p>Comprehensive senior citizen database management</p>
			</div>
			<div class="feature-card">
				<div class="feature-icon">
					<i class="fas fa-calendar-alt"></i>
				</div>
				<h3>Event Planning</h3>
				<p>Organize and manage senior citizen events</p>
			</div>
			<div class="feature-card">
				<div class="feature-icon">
					<i class="fas fa-chart-line"></i>
				</div>
				<h3>Analytics</h3>
				<p>Track and analyze senior citizen data</p>
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
			
			// Initialize theme
			initializeTheme();
		});

		// Theme functionality
		function initializeTheme() {
			const savedTheme = localStorage.getItem('theme') || 'light';
			document.documentElement.setAttribute('data-theme', savedTheme);
			updateThemeIcon(savedTheme);
		}

		function toggleTheme() {
			const currentTheme = document.documentElement.getAttribute('data-theme');
			const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
			
			document.documentElement.setAttribute('data-theme', newTheme);
			localStorage.setItem('theme', newTheme);
			updateThemeIcon(newTheme);
		}

		function updateThemeIcon(theme) {
			const themeIcon = document.querySelector('.theme-icon');
			if (themeIcon) {
				themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
			}
		}
	</script>
</body>
</html>


