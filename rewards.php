<?php
// rewards.php
session_start();
require_once("inc/config.inc.php");
require_once("inc/auth.inc.php");

// Get user details to pass to JavaScript
$fullname = "";
$email = "";
$phone = "";

if (isLoggedIn()) {
    $uquery = "SELECT * FROM exchangerix_users WHERE user_id='".(int)$_SESSION['userid']."' AND status='active' LIMIT 1";
    $uresult = smart_mysql_query($uquery);
    if (mysqli_num_rows($uresult) > 0) {
        $urow = mysqli_fetch_array($uresult);
        $fullname = trim($urow['fname']." ".$urow['lname']);
        $email = $urow['email'];
        $phone = $urow['phone'];
    }
} else {
    // If user is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Earn Rewards - ChinaitechPay</title>
    <meta name="description" content="Complete tasks to earn rewards and get credit in your ChinaitechPay wallet.">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <!-- Theme color for light/dark -->
    <meta name="theme-color" content="#F7F8FA" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">

    <!-- Preconnects -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style type="text/css">
        :root {
            --primary-color: #0803b6;
            --primary-color-dark: #07056a;
            --primary-color-lightest: #EFF6FF;
            --accent-color: #FFAB00;
            --background-color: #F7F8FA;
            --card-background-color: rgba(255, 255, 255, 0.9);
            --card-border-color: rgba(200, 200, 200, 0.3);
            --text-color-primary: #1A202C;
            --text-color-secondary: #374151;
            --border-color: #E2E8F0;
            --success-color: #10B981;
            --error-color: #EF4444;
            --header-height-mobile: 3.5rem;
            --header-height-desktop: 4rem;
        }
        html.dark :root {
            --primary-color: #07056a;
            --primary-color-dark: #1814ad;
            --primary-color-lightest: #1E3A8A;
            --background-color: #111827;
            --card-background-color: rgba(31, 41, 55, 0.8);
            --card-border-color: rgba(75, 85, 99, 0.4);
            --text-color-primary: #F3F4F6;
            --text-color-secondary: #9CA3AF;
            --border-color: #374151;
            --success-color: #34D399;
            --error-color: #F87171;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding-top: env(safe-area-inset-top);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .app-header {
            background-color: var(--card-background-color);
            color: var(--text-color-primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 30;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease-out;
        }
        .app-header.header-scrolled-down { transform: translateY(-100%); }
        .app-content-area { padding-top: calc(var(--header-height-mobile) + 1rem); padding-bottom: calc(1rem + env(safe-area-inset-bottom)); }
        @media (min-width: 768px) { .app-content-area { padding-top: calc(var(--header-height-desktop) + 1.5rem); padding-bottom: calc(2rem + env(safe-area-inset-bottom)); } }
        .hidden { display: none !important; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; display: flex; align-items: center; justify-content: space-between; height: var(--header-height-mobile); }
        @media (min-width: 768px) { .nav-container { height: var(--header-height-desktop); padding: 0 1.5rem; } }
        .header-logo { height: 32px; object-fit: contain; }
        @media (min-width: 768px) { .header-logo { height: 36px; } }
        .desktop-nav-links { display: none; align-items: center; gap: 0.75rem; }
        .desktop-nav-links a { color: var(--text-color-secondary); font-weight: 500; padding: 0.4rem 0.8rem; font-size: 0.9rem; transition: color 0.2s, background-color 0.2s; display: flex; align-items: center; border-radius: 0.375rem; }
        .desktop-nav-links a i { margin-right: 0.4rem; font-size: 0.9em; }
        .desktop-nav-links a:hover { color: var(--primary-color); background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); }
        .desktop-nav-links a.active { color: var(--primary-color); font-weight: 600; background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); }
        .mobile-menu-overlay { position: fixed; inset: 0; background-color: rgba(0,0,0,0.7); z-index: 60; opacity: 0; pointer-events: none; transition: opacity 0.3s ease-in-out; }
        .mobile-menu-overlay.active { opacity: 1; pointer-events: auto; }
        .mobile-menu { position: fixed; top: 0; left: 0; width: 75%; max-width: 300px; height: 100%; background-color: var(--card-background-color); z-index: 70; transform: translateX(-100%); transition: transform 0.3s ease-in-out, background-color 0.3s ease; padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom); }
        .mobile-menu.active { transform: translateX(0); }
        .mobile-menu .nav-link { display: flex; align-items: center; padding: 1rem 1.5rem; color: var(--text-color-primary); font-size: 1.125rem; font-weight: 500; border-bottom: 1px solid var(--border-color); transition: background-color 0.2s, border-color 0.3s ease; }
        .mobile-menu .nav-link i { margin-right: 0.75rem; }
        .mobile-menu .nav-link:hover { background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); }
        .mobile-menu .nav-link.active { color: var(--primary-color); font-weight: 600; }
        .hamburger-btn { font-size: 1.5rem; color: var(--text-color-primary); background: none; border: none; padding: 0.5rem; transition: color 0.3s ease; }
        @media (min-width: 768px) { .hamburger-btn, .mobile-menu, .mobile-menu-overlay { display: none; } .desktop-nav-links { display: flex; } }
        @media (max-width: 767px) { .desktop-nav-links { display: none; } .hamburger-btn { display: block; } }
        #dark-mode-toggle { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-color-secondary); padding: 0.5rem; margin-left:0.5rem; transition: color 0.2s; }
        #dark-mode-toggle:hover { color: var(--primary-color); }
        .app-footer { background-color: var(--primary-color-dark); color: white; padding: 2rem 1.5rem; text-align: center; transition: background-color 0.3s ease; }
        html.dark .app-footer { background-color: #1E3A8A; }
        .app-footer a { color: var(--accent-color); transition: color 0.2s; }
        .app-footer a:hover { color: #FFD700; }
        .social-icons a { font-size: 1.5rem; margin: 0 0.75rem; }
        .footer-links a { margin: 0 0.5rem; font-size: 0.875rem; }
        @media (min-width: 768px) { .app-footer { padding: 3rem 2rem; } .app-footer .container { max-width: 1200px; margin: 0 auto; } }
        .page-header { text-align: center; margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 2.25rem; font-weight: 700; color: var(--primary-color); margin-bottom: 0.5rem; }
        .page-header p { font-size: 1.1rem; color: var(--text-color-secondary); margin-bottom: 1.5rem; }
        .tasks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; max-width: 1200px; margin: 0 auto; }
        @media (min-width: 640px) { .tasks-grid { gap: 1.5rem; } }
        @media (min-width: 1024px) { .tasks-grid { gap: 1.75rem; } }
        .task-card { background-color: var(--card-background-color); border: 1px solid var(--card-border-color); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; text-align: left; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .task-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .task-icon { font-size: 2rem; color: var(--primary-color); margin-bottom: 0.85rem; }
        .task-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.35rem; color: var(--text-color-primary); }
        .task-reward { font-size: 0.95rem; font-weight: 600; color: var(--success-color); margin-bottom: 0.75rem; }
        .task-description { font-size: 0.92rem; color: var(--text-color-secondary); flex-grow: 1; margin-bottom: 1rem; }
        .btn { padding: 0.75rem 1.1rem; border: none; border-radius: 0.5rem; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; }
        .btn i { font-size: 1rem; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: var(--primary-color-dark); }
        .btn-secondary { background-color: transparent; color: var(--primary-color); border: 2px solid var(--primary-color); }
        .btn-secondary:hover { background-color: var(--primary-color); color: #fff; }
        .btn-outline { background-color: transparent; color: var(--text-color-secondary); border: 1px solid var(--border-color); width: auto; padding: 0.5rem 1rem; }
        .btn-outline:hover { background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); color: var(--primary-color); }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; backdrop-filter: blur(5px); }
        .modal-overlay.active { display: flex; }
        .modal-content { background-color: var(--background-color); padding: 1.25rem; border-radius: 0.75rem; width: 100%; max-width: 520px; position: relative; box-shadow: 0 5px 20px rgba(0,0,0,0.2); border: 1px solid var(--card-border-color); }
        .modal-content h2 { margin-bottom: 1rem; text-align: center; color: var(--primary-color); font-size: 1.5rem; font-weight: 700; padding: 0 2.5rem; }
        .rules-list { list-style-type: none; padding-left: 0; }
        .rules-list li { display: flex; align-items: flex-start; margin-bottom: 0.85rem; font-size: 0.95rem; }
        .rules-list i { color: var(--primary-color); margin-right: 0.75rem; font-size: 1.1rem; margin-top: 0.2rem; }
        .close-modal-btn { position: absolute; top: 1rem; right: 1rem; cursor: pointer; color: var(--text-color-secondary); border: 1px solid var(--border-color); background: transparent; line-height: 1; font-size: 0.9rem; font-weight: 600; padding: 0.35rem 0.6rem; border-radius: 6px; transition: color 0.2s ease, background-color 0.2s ease; }
        .close-modal-btn:hover { color: #fff; background-color: var(--error-color); border-color: var(--error-color); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; color: var(--text-color-secondary); }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="url"], .form-group textarea, .form-group input[type="file"] { width: 100%; padding: 0.725rem 0.95rem; border: 1px solid var(--border-color); border-radius: 0.5rem; font-size: 1rem; background-color: var(--card-background-color); color: var(--text-color-primary); transition: border-color 0.2s, box-shadow 0.2s, background-color 0.3s ease; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color) 20%, transparent); }
        .btn-submit { background-color: var(--success-color); color: #fff; width: 100%; }
        .btn-submit:hover { background-color: color-mix(in srgb, var(--success-color) 90%, black); }
        #form-message { text-align: center; padding: 0.6rem; margin-bottom: 1rem; border-radius: 0.5rem; display: none; font-size: 0.9rem; }
        #form-message.success { background-color: color-mix(in srgb, var(--success-color) 15%, transparent); color: var(--success-color); display: block; }
        #form-message.error { background-color: color-mix(in srgb, var(--error-color) 15%, transparent); color: var(--error-color); display: block; }
        #modal-success-state { display: none; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 0; }
        #modal-success-state.active { display: flex; }
        .success-icon { width: 96px; height: 96px; border-radius: 50%; background-color: var(--success-color); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; cursor: pointer; border: 5px solid color-mix(in srgb, var(--success-color) 80%, white); transition: transform 0.2s ease, box-shadow 0.2s ease; animation: pop-in 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55); }
        .success-icon:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .success-icon .fa-check { font-size: 1.75rem; margin-top: 2px; margin-bottom: 5px; }
        @keyframes pop-in { 0% { transform: scale(0.5); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }

        /* Progress Tracking */
        .progress-section { max-width: 1200px; margin: 0 auto 1.5rem; background-color: var(--card-background-color); border: 1px solid var(--card-border-color); border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .progress-title { font-size: 1.35rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem; }
        .progress-stats { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 0.75rem; text-align: center; }
        .stat-item { background: color-mix(in srgb, var(--primary-color) 4%, transparent); border: 1px dashed var(--card-border-color); padding: 0.9rem; border-radius: 0.6rem; }
        .stat-value { font-size: 1.35rem; font-weight: 800; color: var(--success-color); }
        .stat-label { font-size: 0.85rem; color: var(--text-color-secondary); }
        .skeleton { position: relative; overflow: hidden; background: color-mix(in srgb, var(--border-color) 35%, transparent); border-radius: 0.5rem; }
        .skeleton::after { content: ""; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); animation: shimmer 1.2s infinite; }
        @keyframes shimmer { 100% { transform: translateX(100%); } }
        
        /* Focus styles */
        :focus-visible { outline: 3px solid color-mix(in srgb, var(--primary-color) 55%, white); outline-offset: 2px; border-radius: 6px; }
    </style>
</head>
<body class="antialiased">
    <header class="app-header" role="banner">
        <div class="nav-container">
            <div class="flex items-center">
                <button id="hamburger-btn" class="hamburger-btn md:hidden mr-2" aria-label="Open menu" aria-expanded="false"><i class="fas fa-bars" aria-hidden="true"></i></button>
                <img src="https://chinaitechpay.com/blog/wp-content/uploads/2025/05/chinaitechpay-logo-header.png" alt="ChinaitechPay Logo" class="header-logo" loading="lazy" decoding="async">
            </div>
            <nav class="desktop-nav-links" aria-label="Primary">
                <a href="https://chinaitechpay.com/start.php" class="nav-link" rel="noopener noreferrer"><i class="fa fa-exchange" aria-hidden="true"></i> Home</a>
                <a href="https://chinaitechpay.com/myaccount.php" class="nav-link" rel="noopener noreferrer"><i class="fa fa-line-chart" aria-hidden="true"></i> Dashboard</a>
                <a href="https://chinaitechpay.com/mybalance.php" class="nav-link" rel="noopener noreferrer"><i class="fa fa-wallet" aria-hidden="true"></i> My Wallet</a>
                <a href="https://chinaitechpay.com/invite.php" class="nav-link" rel="noopener noreferrer"><i class="fa fa-dollar" aria-hidden="true"></i> Refer & Earn</a>
                <a href="https://chinaitechpay.com/myprofile.php" class="nav-link" rel="noopener noreferrer"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> My Profile</a>
                <a href="https://chinaitechpay.com/help.php" class="nav-link" rel="noopener noreferrer"><i class="fa fa-info-circle" aria-hidden="true"></i> Help</a>
            </nav>
            <button id="dark-mode-toggle" aria-label="Toggle dark mode" title="Toggle theme"><i class="fas fa-moon" aria-hidden="true"></i></button>
        </div>
    </header>

    <div id="mobile-menu-overlay" class="mobile-menu-overlay" tabindex="-1" aria-hidden="true"></div>
    <nav id="mobile-menu" class="mobile-menu" aria-label="Mobile primary navigation" aria-hidden="true">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <img src="https://chinaitechpay.com/blog/wp-content/uploads/2025/05/chinaitechpay-logo-header.png" alt="ChinaitechPay Logo" class="header-logo" loading="lazy" decoding="async">
            <button id="mobile-menu-close-btn" class="hamburger-btn" aria-label="Close menu"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <a href="https://chinaitechpay.com/" class="nav-link" rel="noopener noreferrer"><i class="fa fa-exchange" aria-hidden="true"></i> Home</a>
        <a href="https://chinaitechpay.com/myaccount.php" class="nav-link" rel="noopener noreferrer"><i class="fas fa-user-circle" aria-hidden="true"></i> My Account</a>
        <a href="https://chinaitechpay.com/mybalance.php" class="nav-link" rel="noopener noreferrer"><i class="fa fa-wallet" aria-hidden="true"></i> My Wallet</a>
        <a href="https://chinaitechpay.com/invite.php" class="nav-link" rel="noopener noreferrer"><i class="fa fa-dollar" aria-hidden="true"></i> Refer & Earn</a>
        <a href="https://chinaitechpay.com/testimonials.php" class="nav-link" rel="noopener noreferrer"><i class="fas fa-quote-left" aria-hidden="true"></i> Testimonials</a>
        <a href="https://uk.chinaitech.net/shop" class="nav-link" rel="noopener noreferrer"><i class="fas fa-shopping-cart" aria-hidden="true"></i> Shop Cards</a>
    </nav>

    <main class="app-content-area px-4 md:px-6" role="main">
        <section class="progress-section" aria-labelledby="progress-title">
            <h2 id="progress-title" class="progress-title">Your Rewards Progress</h2>
            <div id="progress-stats" class="progress-stats" aria-live="polite">
                <div class="stat-item">
                    <div id="skeleton-completed" class="skeleton h-6 mb-2"></div>
                    <div id="stat-completed" class="stat-value" hidden>0</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-item">
                    <div id="skeleton-earned" class="skeleton h-6 mb-2"></div>
                    <div id="stat-earned" class="stat-value" hidden>$0.00</div>
                    <div class="stat-label">Total Earned</div>
                </div>
                <div class="stat-item">
                    <div id="skeleton-pending" class="skeleton h-6 mb-2"></div>
                    <div id="stat-pending" class="stat-value" hidden>0</div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </section>

        <div class="page-header">
            <h1>Earn Unlimited Money!</h1>
            <p>Complete simple tasks to get rewarded for your support.</p>
            <button id="rules-btn" class="btn btn-outline" type="button"><i class="fas fa-gavel" aria-hidden="true"></i> Read the Program Rules</button>
        </div>

        <section class="tasks-grid" aria-label="Available reward tasks">
            <article class="task-card" aria-labelledby="task-title-review">
                <div class="task-icon" aria-hidden="true"><i class="fas fa-star"></i></div>
                <h3 id="task-title-review" class="task-title">Leave a 5-Star Review</h3>
                <p class="task-reward">Reward: $0.25 USD</p>
                <div class="task-description">
                    <p>Leave a positive 5-star rating on our BestChange page. Your feedback helps us grow.</p>
                    <p class="mt-2"><b>Note:</b> Description must be at least 170 characters. Only one review per user is allowed.</p>
                    <p class="mt-2">You can share the review link to your friends and you as a user submit the proof and make money instantly.</p>
                </div>
                <a href="https://www.bestchange.com/chinaitechpay-exchanger.html" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Review on BestChange</a>
                <button class="btn btn-secondary open-modal-btn" data-task="review" type="button"><i class="fas fa-upload" aria-hidden="true"></i> Upload Proof & Claim</button>
            </article>

            <article class="task-card" aria-labelledby="task-title-video">
                <div class="task-icon" aria-hidden="true"><i class="fas fa-video"></i></div>
                <h3 id="task-title-video" class="task-title">Make a Testimonial Video</h3>
                <p class="task-reward">Reward: $1.00 USD</p>
                <div class="task-description">
                    <p>Create a 1-minute or higher video about your experience with ChinaitechPay.</p>
                    <p class="mt-2"><b>Note:</b> Optimize for TikTok, YouTube, or Instagram Reels. Share the video link you upload with us to claim your reward.</p>
                    <p class="mt-2">We can offer up to <b>$10</b> for your video, depending on its quality. Many YouTubers also earn significant revenue by uploading content to their channels.</p>
                </div>
                <button class="btn btn-secondary open-modal-btn" data-task="video" type="button"><i class="fas fa-upload" aria-hidden="true"></i> Submit Proof & Claim</button>
            </article>

            <article class="task-card" aria-labelledby="task-title-follow">
                <div class="task-icon" aria-hidden="true"><i class="fas fa-share-alt"></i></div>
                <h3 id="task-title-follow" class="task-title">Follow on Social Media</h3>
                <p class="task-reward">Reward: $0.05 USD</p>
                <div class="task-description">
                    <p>Follow our official accounts on X (Twitter) and Facebook to stay connected and support our updates.</p>
                    <p class="mt-2"><b>Note:</b> You must follow both accounts to qualify. Upload a single screenshot confirming your follows.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="https://x.com/chinaitech" target="_blank" rel="noopener noreferrer" class="btn btn-primary"><i class="fab fa-x-twitter" aria-hidden="true"></i> X (Twitter)</a>
                    <a href="https://www.facebook.com/chinaitechs" target="_blank" rel="noopener noreferrer" class="btn btn-primary"><i class="fab fa-facebook-f" aria-hidden="true"></i> Facebook</a>
                </div>
                <button class="btn btn-secondary open-modal-btn mt-3" data-task="follow" type="button"><i class="fas fa-upload" aria-hidden="true"></i> Upload Proof & Claim</button>
            </article>

            <article class="task-card" aria-labelledby="task-title-blog">
                <div class="task-icon" aria-hidden="true"><i class="fas fa-file-pen"></i></div>
                <h3 id="task-title-blog" class="task-title">Write a Blog Post</h3>
                <p class="task-reward">Reward: $0.50 USD</p>
                <div class="task-description">
                    <p>Write and publish an article about ChinaitechPay services. Must include a link to our website.</p>
                    <p class="mt-2"><b>Note:</b> We do not accept Blogspot or other free blogging platforms. Must be a real website.</p>
                </div>
                <button class="btn btn-secondary open-modal-btn" data-task="blog" type="button"><i class="fas fa-upload" aria-hidden="true"></i> Submit Proof & Claim</button>
            </article>

            <article class="task-card" aria-labelledby="task-title-youtube">
                <div class="task-icon" aria-hidden="true"><i class="fab fa-youtube"></i></div>
                <h3 id="task-title-youtube" class="task-title">Subscribe to YouTube Channel</h3>
                <p class="task-reward">Reward: $0.02 USD</p>
                <div class="task-description">
                    <p>Subscribe to our official YouTube channel for tutorials, updates, and more.</p>
                    <p class="mt-2"><b>Note:</b> Upload a screenshot showing your subscription confirmation. Only one per user.</p>
                </div>
                <a href="https://www.youtube.com/@chinaitechpay" target="_blank" rel="noopener noreferrer" class="btn btn-primary"><i class="fab fa-youtube" aria-hidden="true"></i> Subscribe Now</a>
                <button class="btn btn-secondary open-modal-btn" data-task="youtube" type="button"><i class="fas fa-upload" aria-hidden="true"></i> Upload Proof & Claim</button>
            </article>
        </section>
    </main>

    <div id="proof-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title" aria-hidden="true">
        <div class="modal-content" role="document">
            <button class="close-modal-btn" aria-label="Close modal">Cancel</button>

            <form id="proof-form" enctype="multipart/form-data" method="post" novalidate>
                <h2 id="modal-title">Submit Your Proof</h2>
                <div id="form-message" aria-live="polite" role="status"></div>
                <input type="hidden" id="task-type" name="task_type" value="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-group"><label for="email">ChinaitechPay Email</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required readonly autocomplete="email"></div>
                <div class="form-group"><label for="name">Your Name</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($fullname); ?>" required readonly autocomplete="name"></div>

                <div class="form-group hidden" id="group-handle"><label for="handle">Your Handle / Username</label><input type="text" id="handle" name="handle" placeholder="@yourhandle" inputmode="text" maxlength="50"></div>
                <div class="form-group hidden" id="group-proof-url"><label for="proof-url">Proof URL</label><input type="url" id="proof-url" name="proof_url" placeholder="E.g. Website link you submitted." inputmode="url"></div>
                <div class="form-group hidden" id="group-video-url"><label for="video-url">Proof Video URL</label><input type="url" id="video-url" name="video_url" placeholder="E.g. YouTube/TikTok/Instagram link" inputmode="url"></div>

                <div class="form-group" id="group-screenshot">
                    <label for="screenshot">Upload Screenshot of Proof</label>
                    <input type="file" id="screenshot" name="screenshot" accept="image/png, image/jpeg, application/pdf">
                </div>

                <div class="form-group hidden" id="group-notes"><label for="notes">Notes (optional)</label><textarea id="notes" name="notes" placeholder="Anything else we should know?" rows="3" maxlength="500"></textarea></div>
                <button type="submit" class="btn btn-submit"><i class="fas fa-paper-plane" aria-hidden="true"></i> Submit Proof</button>
            </form>

            <div id="modal-success-state" class="hidden text-center" aria-live="polite">
                <div class="success-icon close-modal-btn" role="button" tabindex="0" aria-label="Close success and modal">
                    <i class="fas fa-check" aria-hidden="true"></i>
                    <span>Done</span>
                </div>
                <h2 class="text-2xl font-bold mt-6 mb-2" style="color: var(--success-color);">Submission Received!</h2>
                <p class="text-base" style="color: var(--text-color-secondary);">
                    Thank you! Our team will review your proof. If valid, your reward will be credited to your account within 24-48 hours.
                </p>
            </div>
        </div>
    </div>

    <div id="rules-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="rules-title" aria-hidden="true">
        <div class="modal-content">
            <button class="close-rules-modal-btn close-modal-btn" aria-label="Close modal">Close</button>
            <h2 id="rules-title">Rewards Program Rules</h2>
            <p class="text-center text-sm mb-6" style="color: var(--text-color-secondary);">Don't think you're too smart? We value fairness and integrity. Please review the rules to ensure eligibility.</p>
            <ul class="rules-list">
                <li><i class="fas fa-user-slash" aria-hidden="true"></i> <div><b>No Duplicate Accounts:</b> Multiple accounts per user are prohibited. Violations will result in permanent bans.</div></li>
                <li><i class="fas fa-check-double" aria-hidden="true"></i> <div><b>Single Submission Per Task:</b> Rewards are limited to one per task type per user.</div></li>
                <li><i class="fas fa-lightbulb" aria-hidden="true"></i> <div><b>Original Content Required:</b> All submissions must be authentic and original. Plagiarism leads to disqualification.</div></li>
                <li><i class="fas fa-award" aria-hidden="true"></i> <div><b>Quality Standards:</b> Submissions must meet quality criteria; low-effort or fraudulent entries will be rejected.</div></li>
                <li><i class="fas fa-hourglass-half" aria-hidden="true"></i> <div><b>Review Timeline:</b> Allow 1-24 hours for verification. Rewards are issued post-approval.</div></li>
                <li><i class="fas fa-comment-dots" aria-hidden="true"></i> <div><b>Genuine Feedback:</b> Testimonials must reflect your true experience with our services.</div></li>
                <li><i class="fas fa-sync-alt" aria-hidden="true"></i> <div><b>Program Changes:</b> ChinaitechPay may update rules, tasks, or rewards without notice.</div></li>
            </ul>
        </div>
    </div>

    <footer class="app-footer">
        <div class="container mx-auto">
            <p class="text-sm mb-4">This website Powered by <strong>China Technology Team</strong> Since 2014 | A Leading Global Technology Company.</p>
            <div class="social-icons mb-4">
                <a href="https://www.facebook.com/chinaitechs" target="_blank" aria-label="Facebook" rel="noopener noreferrer"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                <a href="https://x.com/chinaitech" target="_blank" aria-label="X" rel="noopener noreferrer"><i class="fab fa-x-twitter" aria-hidden="true"></i></a>
                <a href="https://www.youtube.com/@chinaitechpay" target="_blank" aria-label="YouTube" rel="noopener noreferrer"><i class="fab fa-youtube" aria-hidden="true"></i></a>
            </div>
            <div class="footer-links">
                <a href="https://chinaitechpay.com/terms.php" rel="noopener noreferrer">Terms</a>
                <a href="https://chinaitechpay.com/scams.php" rel="noopener noreferrer">About Scams</a>
                <a href="https://chinaitechpay.com/contact.php" rel="noopener noreferrer">Contact</a>
                <a href="https://chinaitechpay.com/aml.php" rel="noopener noreferrer">AML Policy</a>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- HEADER, DARK MODE, AND MOBILE MENU SCRIPT ---
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenuCloseBtn = document.getElementById('mobile-menu-close-btn');
        const darkModeToggleBtn = document.getElementById('dark-mode-toggle');
        const appHeaderEl = document.querySelector('.app-header');
        let lastScrollTop = 0;
        let lastActiveTrigger = null;

        function setThemeColorMeta(isDark) {
            const metaTags = document.querySelectorAll('meta[name="theme-color"]');
            metaTags.forEach(tag => {
                const media = tag.getAttribute('media');
                if (!media) tag.setAttribute('content', isDark ? '#111827' : '#F7F8FA');
            });
        }

        // Persistent Dark Mode with localStorage
        function applyDarkModePreference() {
            const prefersDark = localStorage.getItem('darkMode') === 'true';
            document.documentElement.classList.toggle('dark', prefersDark);
            if (darkModeToggleBtn) darkModeToggleBtn.innerHTML = prefersDark ? '<i class="fas fa-sun" aria-hidden="true"></i>' : '<i class="fas fa-moon" aria-hidden="true"></i>';
            setThemeColorMeta(prefersDark);
        }

        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            if (darkModeToggleBtn) darkModeToggleBtn.innerHTML = isDark ? '<i class="fas fa-sun" aria-hidden="true"></i>' : '<i class="fas fa-moon" aria-hidden="true"></i>';
            setThemeColorMeta(isDark);
        }

        function toggleMobileMenu(forceClose = false) {
            const isActive = mobileMenu.classList.contains('active');
            if (forceClose || isActive) {
                mobileMenu.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
                mobileMenu.setAttribute('aria-hidden', 'true');
                mobileMenuOverlay.setAttribute('aria-hidden', 'true');
                if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'false');
                if (lastActiveTrigger) lastActiveTrigger.focus();
            } else {
                mobileMenu.classList.add('active');
                mobileMenuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                mobileMenu.setAttribute('aria-hidden', 'false');
                mobileMenuOverlay.setAttribute('aria-hidden', 'false');
                if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'true');
                lastActiveTrigger = document.activeElement;
                mobileMenu.querySelector('a, button')?.focus();
            }
        }

        function handleHeaderScroll() {
            if (!appHeaderEl) return;
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > lastScrollTop && currentScroll > appHeaderEl.offsetHeight) {
                appHeaderEl.classList.add('header-scrolled-down');
            } else {
                appHeaderEl.classList.remove('header-scrolled-down');
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        }

        applyDarkModePreference();
        if (darkModeToggleBtn) darkModeToggleBtn.addEventListener('click', toggleDarkMode);
        if (hamburgerBtn) hamburgerBtn.addEventListener('click', (e) => { lastActiveTrigger = e.currentTarget; toggleMobileMenu(); });
        if (mobileMenuOverlay) mobileMenuOverlay.addEventListener('click', () => toggleMobileMenu(true));
        if (mobileMenuCloseBtn) mobileMenuCloseBtn.addEventListener('click', () => toggleMobileMenu(true));
        window.addEventListener('scroll', handleHeaderScroll, { passive: true });
        
        // --- REWARDS PAGE MODAL SCRIPT ---
        const proofModal = document.getElementById('proof-modal');
        const rulesModal = document.getElementById('rules-modal');
        const openModalButtons = document.querySelectorAll('.open-modal-btn');
        const closeModalButtons = document.querySelectorAll('.close-modal-btn');
        const openRulesBtn = document.getElementById('rules-btn');
        const closeRulesBtn = document.querySelector('.close-rules-modal-btn');

        const proofForm = document.getElementById('proof-form');
        const modalSuccessState = document.getElementById('modal-success-state');
        const modalTitle = document.getElementById('modal-title');
        const taskTypeInput = document.getElementById('task-type');
        const formMessage = document.getElementById('form-message');

        const screenshotGroup = document.getElementById('group-screenshot');
        const screenshotInput = document.getElementById('screenshot');
        const handleGroup = document.getElementById('group-handle');
        const handleInput = document.getElementById('handle');
        const proofUrlGroup = document.getElementById('group-proof-url');
        const proofUrlInput = document.getElementById('proof-url');
        const videoUrlGroup = document.getElementById('group-video-url');
        const videoUrlInput = document.getElementById('video-url');
        const notesGroup = document.getElementById('group-notes');

        // Focus trap utility
        function trapFocus(modal) {
            const focusableSelectors = 'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])';
            const focusable = Array.from(modal.querySelectorAll(focusableSelectors)).filter(el => el.offsetParent !== null);
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            function handleTab(e) {
                if (e.key !== 'Tab') return;
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
            modal.addEventListener('keydown', handleTab);
            return () => modal.removeEventListener('keydown', handleTab);
        }

        function openModal(modal) {
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            lastActiveTrigger = document.activeElement;
            const cleanup = trapFocus(modal);
            modal.dataset.trapCleanup = cleanup ? '1' : '';
            modal.querySelector('button, input, select, textarea, a')?.focus();

            function onEsc(e) { if (e.key === 'Escape') closeModal(modal); }
            modal.addEventListener('keydown', onEsc);
            modal.dataset.esc = '1';
        }
        function closeModal(modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (modal.dataset.trapCleanup) { /* listeners removed by returned fn */ }
            if (modal.dataset.esc) { /* esc listener removed automatically on element removal */ }
            if (lastActiveTrigger) lastActiveTrigger.focus();
        }

        if (openRulesBtn) openRulesBtn.addEventListener('click', () => openModal(rulesModal));
        if (closeRulesBtn) closeRulesBtn.addEventListener('click', () => closeModal(rulesModal));
        if (rulesModal) rulesModal.addEventListener('click', (e) => { if (e.target === rulesModal) closeModal(rulesModal); });

        const setupFormForTask = (task) => {
            [handleGroup, proofUrlGroup, videoUrlGroup, notesGroup].forEach(el => el.classList.add('hidden'));
            [handleInput, proofUrlInput, videoUrlInput, screenshotInput].forEach(el => el.required = false);
            screenshotGroup.classList.add('hidden');

            switch (task) {
                case 'review':
                    modalTitle.textContent = 'Proof for 5-Star Review';
                    taskTypeInput.value = '5-Star Review';
                    screenshotGroup.classList.remove('hidden');
                    screenshotInput.required = true;
                    break;
                case 'follow':
                    modalTitle.textContent = 'Proof for Social Media Follow';
                    taskTypeInput.value = 'Social Media Follow';
                    screenshotGroup.classList.remove('hidden');
                    screenshotInput.required = true;
                    break;
                case 'video':
                    modalTitle.textContent = 'Proof for Testimonial Video';
                    taskTypeInput.value = 'Testimonial Video';
                    videoUrlGroup.classList.remove('hidden');
                    videoUrlInput.required = true;
                    handleGroup.classList.remove('hidden');
                    handleInput.required = true;
                    notesGroup.classList.remove('hidden');
                    break;
                case 'blog':
                    modalTitle.textContent = 'Proof for Blog Post';
                    taskTypeInput.value = 'Blog Post';
                    proofUrlGroup.classList.remove('hidden');
                    proofUrlInput.required = true;
                    notesGroup.classList.remove('hidden');
                    break;
                case 'youtube':
                    modalTitle.textContent = 'Proof for YouTube Subscription';
                    taskTypeInput.value = 'YouTube Subscription';
                    screenshotGroup.classList.remove('hidden');
                    screenshotInput.required = true;
                    handleGroup.classList.remove('hidden');
                    handleInput.required = true;
                    break;
            }
        };

        openModalButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const task = button.dataset.task;
                setupFormForTask(task);
                openModal(proofModal);
            });
        });

        closeModalButtons.forEach(button => button.addEventListener('click', () => {
            // If inside proof modal, also reset
            if (proofModal.classList.contains('active')) resetProofForm();
            closeModal(button.closest('.modal-overlay'));
        }));
        if (proofModal) proofModal.addEventListener('click', (e) => { if (e.target === proofModal) { resetProofForm(); closeModal(proofModal); } });

        function resetProofForm() {
            proofForm.reset();
            modalSuccessState.classList.remove('active');
            modalSuccessState.classList.add('hidden');
            proofForm.style.display = 'block';
            document.getElementById('email').value = "<?php echo htmlspecialchars($email); ?>";
            document.getElementById('name').value = "<?php echo htmlspecialchars($fullname); ?>";
            formMessage.style.display = 'none';
            formMessage.textContent = '';
            formMessage.className = '';
        }

        function showError(msg) {
            formMessage.textContent = msg;
            formMessage.className = 'error';
            formMessage.style.display = 'block';
        }

        function isValidUrl(value) {
            try { new URL(value); return true; } catch { return false; }
        }

        proofForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            formMessage.style.display = 'none';

            // Client-side Validation Improvements
            let hasError = false;

            if (screenshotInput.required) {
                if (!screenshotInput.files.length) {
                    showError('Please upload a screenshot as proof.');
                    hasError = true;
                } else {
                    const file = screenshotInput.files[0];
                    const allowedTypes = ['image/png','image/jpeg','application/pdf'];
                    const maxBytes = 5 * 1024 * 1024; // 5MB
                    if (!allowedTypes.includes(file.type)) { showError('Invalid file type. Use PNG, JPG/JPEG, or PDF.'); hasError = true; }
                    if (file.size > maxBytes) { showError('File too large. Maximum size is 5 MB.'); hasError = true; }
                }
            }

            if (proofUrlInput.required && !proofUrlInput.value.trim()) { showError('Please provide a valid proof URL.'); hasError = true; }
            if (proofUrlInput.required && proofUrlInput.value.trim() && !isValidUrl(proofUrlInput.value.trim())) { showError('Proof URL is not valid.'); hasError = true; }

            if (videoUrlInput.required && !videoUrlInput.value.trim()) { showError('Please provide a valid video URL.'); hasError = true; }
            if (videoUrlInput.required && videoUrlInput.value.trim() && !isValidUrl(videoUrlInput.value.trim())) { showError('Video URL is not valid.'); hasError = true; }

            if (handleInput.required && !handleInput.value.trim()) { showError('Please provide your handle/username.'); hasError = true; }

            if (hasError) return;

            const submitButton = proofForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Submitting...';

            const formData = new FormData(proofForm);

            try {
                const response = await fetch('reward_proof.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });

                let result;
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) result = await response.json();
                else { const text = await response.text(); try { result = JSON.parse(text); } catch { throw new Error('Unexpected response'); } }

                if (response.ok && result && result.status === 'success') {
                    proofForm.style.display = 'none';
                    modalSuccessState.classList.remove('hidden');
                    modalSuccessState.classList.add('active');
                } else {
                    showError(result && result.message ? result.message : 'Submission failed. Please try again.');
                }
            } catch (error) {
                showError('An unexpected error occurred. Please try again.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane" aria-hidden="true"></i> Submit Proof';
            }
        });

        // Fetch and Display User Progress
        async function fetchUserProgress() {
            const elCompleted = document.getElementById('stat-completed');
            const elEarned = document.getElementById('stat-earned');
            const elPending = document.getElementById('stat-pending');
            const skCompleted = document.getElementById('skeleton-completed');
            const skEarned = document.getElementById('skeleton-earned');
            const skPending = document.getElementById('skeleton-pending');
            try {
                const response = await fetch('get_rewards_progress.php', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const data = await response.json();
                if (typeof data.completed !== 'undefined') {
                    elCompleted.textContent = String(data.completed);
                } else {
                    elCompleted.textContent = '0';
                }
                if (typeof data.earned !== 'undefined') {
                    const earned = Number(data.earned);
                    elEarned.textContent = isFinite(earned) ? `$${earned.toFixed(2)}` : '$0.00';
                } else {
                    elEarned.textContent = '$0.00';
                }
                if (typeof data.pending !== 'undefined') {
                    elPending.textContent = String(data.pending);
                } else {
                    elPending.textContent = '0';
                }
            } catch (error) {
                // Silent fail, keep zeros
            } finally {
                [skCompleted, skEarned, skPending].forEach(el => el.setAttribute('hidden', 'true'));
                [elCompleted, elEarned, elPending].forEach(el => el.removeAttribute('hidden'));
            }
        }

        fetchUserProgress();
    });
    </script>
</body>
</html>
