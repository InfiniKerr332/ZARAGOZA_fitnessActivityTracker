<?php
$pageTitle = 'Welcome';
require_once __DIR__ . '/includes/header.php';
if (isLoggedIn()) { redirect(isAdmin() ? '/admin/dashboard.php' : '/user/dashboard.php'); }
?>
<nav class="landing-nav">
    <a href="<?= BASE_URL ?>/" class="sidebar-logo">
        <div class="logo-icon"><i data-lucide="activity"></i></div>
        <span>FitTrack Pro</span>
    </a>
    <div class="nav-actions">
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-ghost">Log In</a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary">Get Started</a>
    </div>
</nav>

<section class="hero">
    <div class="hero-content animate-in">
        <h1>Track Your Fitness Journey Like a Pro</h1>
        <p>Log workouts, monitor nutrition, track body metrics, and achieve your goals with powerful analytics and insights.</p>
        <div class="hero-buttons">
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg">
                <i data-lucide="rocket"></i> Start Free
            </a>
            <a href="#features" class="btn btn-outline btn-lg">
                <i data-lucide="info"></i> Learn More
            </a>
        </div>
    </div>
</section>

<section class="features" id="features">
    <h2>Everything You Need</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon stat-icon purple"><i data-lucide="flame"></i></div>
            <h3>Activity Tracking</h3>
            <p>Log every workout with detailed metrics including duration, distance, sets, reps, and calories burned.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon stat-icon green"><i data-lucide="apple"></i></div>
            <h3>Nutrition Logging</h3>
            <p>Track meals with macronutrient breakdown — protein, carbs, fat, fiber, and calorie counts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon stat-icon blue"><i data-lucide="ruler"></i></div>
            <h3>Body Metrics</h3>
            <p>Monitor weight, BMI, body fat percentage, and body measurements over time.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon stat-icon orange"><i data-lucide="target"></i></div>
            <h3>Goal Setting</h3>
            <p>Set fitness goals with deadlines and track your progress with visual indicators.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon stat-icon cyan"><i data-lucide="bar-chart-3"></i></div>
            <h3>Advanced Reports</h3>
            <p>Get detailed analytics with charts, trends, and insights powered by advanced SQL queries.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon stat-icon red"><i data-lucide="shield-check"></i></div>
            <h3>Secure & Verified</h3>
            <p>Email-verified accounts with secure authentication and admin oversight.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
