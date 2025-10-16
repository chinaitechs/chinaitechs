<?php
/*******************************************************************\
 * ChinaitechPay - Professional Deposit Page (v16 - Enhanced Design)
 *
 * DESCRIPTION:
 * Enhanced professional deposit page with modern UI/UX design,
 * improved visual hierarchy, and better user experience.
 *
 * FEATURES:
 * - Modern gradient design with glassmorphism effects
 * - Enhanced payment method cards with icons
 * - Improved form validation and user feedback
 * - Better responsive design for all devices
 * - Professional animations and micro-interactions
 *
 * BUILT BY: AI Assistant
\*******************************************************************/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once("inc/auth.inc.php");

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// CSRF Token Generation for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/////////////// PAGE CONFIG ///////////////
$PAGE_TITLE = "Fund Your Wallet";
require_once("inc/header.inc.php");
?>

<!-- Enhanced CSS for Professional Deposit Page -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    --danger-gradient: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    --dark: #1a1a2e;
    --darker: #0f0f1e;
    --light: #f8f9fa;
    --white: #ffffff;
    --gray: #6c757d;
    --light-gray: #e9ecef;
    --border-radius: 20px;
    --border-radius-sm: 12px;
    --shadow: 0 20px 60px rgba(0,0,0,0.1);
    --shadow-hover: 0 30px 80px rgba(0,0,0,0.15);
    --glass-bg: rgba(255, 255, 255, 0.25);
    --glass-border: rgba(255, 255, 255, 0.18);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    background-attachment: fixed;
    color: var(--dark);
    line-height: 1.6;
    min-height: 100vh;
    padding-bottom: 40px;
    overflow-x: hidden;
}

/* Background Animation */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
    z-index: -1;
    animation: backgroundShift 20s ease-in-out infinite;
}

@keyframes backgroundShift {
    0%, 100% { transform: translateX(0) translateY(0); }
    33% { transform: translateX(-30px) translateY(-30px); }
    66% { transform: translateX(30px) translateY(30px); }
}

.deposit-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 20px;
    position: relative;
    z-index: 1;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
    animation: fadeInDown 0.8s ease;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.page-title {
    font-size: 3.5rem;
    font-weight: 900;
    background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    text-shadow: 0 4px 20px rgba(0,0,0,0.1);
    letter-spacing: -0.02em;
}

.page-subtitle {
    font-size: 1.3rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 400;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.5;
}

.deposit-form-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 3rem;
    animation: fadeInUp 1s ease;
    position: relative;
    overflow: hidden;
}

.deposit-form-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-section-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid transparent;
    border-image: var(--primary-gradient) 1;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
}

.form-section-title::before {
    content: '';
    position: absolute;
    left: 0;
    bottom: -3px;
    width: 60px;
    height: 3px;
    background: var(--primary-gradient);
    border-radius: 2px;
}

.form-section-title i {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 1.5rem;
}

.form-group {
    margin-bottom: 2rem;
}

.form-group label {
    display: block;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.75rem;
    letter-spacing: 0.01em;
}

.form-control, .form-select {
    width: 100%;
    padding: 1.2rem 1.5rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius-sm);
    font-size: 1.1rem;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    color: var(--dark);
}

.form-control:focus, .form-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.95);
}

.form-control::placeholder {
    color: var(--gray);
    font-weight: 400;
}

.payment-method-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 20px;
    margin-top: 1.5rem;
}

.payment-method-card {
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius-sm);
    padding: 2rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
    min-height: 140px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.payment-method-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--primary-gradient);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.payment-method-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-hover);
    border-color: rgba(102, 126, 234, 0.5);
}

.payment-method-card:hover::before {
    opacity: 0.1;
}

.payment-method-card.selected {
    background: var(--primary-gradient);
    color: white;
    border-color: #667eea;
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
    transform: translateY(-5px);
}

.payment-method-card.selected::before {
    opacity: 0;
}

.payment-method-card i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
    transition: all 0.3s ease;
}

.payment-method-card:hover i {
    transform: scale(1.1);
}

.payment-method-card.selected i {
    color: white;
    transform: scale(1.1);
}

.payment-method-card .method-name {
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 0.01em;
}

.payment-method-card .method-fee {
    font-size: 0.85rem;
    opacity: 0.8;
    margin-top: 0.5rem;
    font-weight: 500;
}

.payment-details-box {
    display: none;
    margin-top: 2rem;
    padding: 2rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius);
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(15px);
    animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.payment-details-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--success-gradient);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.payment-details-box h5 {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.payment-details-box h5 i {
    background: var(--success-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 1.2rem;
}

.wallet-info {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    padding: 1.2rem 1.5rem;
    border-radius: var(--border-radius-sm);
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    gap: 15px;
    border: 1px solid rgba(255, 255, 255, 0.5);
}

.wallet-info span {
    flex-grow: 1;
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark);
    word-break: break-all;
    letter-spacing: 0.02em;
}

.copy-btn {
    cursor: pointer;
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: var(--border-radius-sm);
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.copy-btn:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.copy-btn:active {
    transform: scale(0.95);
}

.bank-details {
    list-style: none;
    padding: 0;
    background: rgba(255, 255, 255, 0.8);
    border-radius: var(--border-radius-sm);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.5);
}

.bank-details li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.2rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    transition: background 0.3s ease;
}

.bank-details li:hover {
    background: rgba(102, 126, 234, 0.05);
}

.bank-details li:last-child {
    border-bottom: none;
}

.bank-details strong {
    color: var(--gray);
    font-weight: 600;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.upload-area {
    border: 3px dashed rgba(102, 126, 234, 0.3);
    border-radius: var(--border-radius);
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(10px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.upload-area::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--primary-gradient);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.upload-area:hover {
    border-color: #667eea;
    transform: translateY(-5px) scale(1.02);
    box-shadow: var(--shadow);
}

.upload-area:hover::before {
    opacity: 0.1;
}

.upload-area.has-file {
    border-color: #38ef7d;
    background: rgba(56, 239, 125, 0.1);
    border-style: solid;
    transform: translateY(-3px);
}

.upload-area i {
    font-size: 3.5rem;
    color: #667eea;
    margin-bottom: 1.5rem;
    display: block;
    transition: all 0.3s ease;
}

.upload-area:hover i {
    transform: scale(1.1);
    color: #764ba2;
}

.upload-area span {
    color: var(--dark);
    font-weight: 600;
    font-size: 1.2rem;
    position: relative;
    z-index: 1;
    letter-spacing: 0.01em;
}

#proof-preview {
    max-width: 250px;
    margin-top: 1.5rem;
    border-radius: var(--border-radius-sm);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    display: none;
    border: 3px solid rgba(255, 255, 255, 0.8);
}

.summary-box {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(15px);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-top: 2.5rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(255, 255, 255, 0.5);
    position: relative;
    overflow: hidden;
}

.summary-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--primary-gradient);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.summary-item:hover {
    background: rgba(102, 126, 234, 0.05);
    margin: 0 -1rem;
    padding: 1rem;
    border-radius: var(--border-radius-sm);
}

.summary-item .label {
    color: var(--gray);
    font-weight: 500;
    font-size: 1rem;
}

.summary-item .value {
    color: var(--dark);
    font-weight: 700;
    font-size: 1.2rem;
}

.summary-item.total {
    font-size: 1.6rem;
    border-top: 3px solid rgba(102, 126, 234, 0.2);
    margin-top: 1rem;
    padding-top: 1.5rem;
    background: rgba(102, 126, 234, 0.05);
    margin: 1rem -1rem 0 -1rem;
    padding: 1.5rem 1rem 1rem 1rem;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
}

.summary-item.total .value {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 1.8rem;
    font-weight: 800;
}

.submit-deposit-btn {
    width: 100%;
    background: var(--primary-gradient);
    color: white;
    padding: 1.5rem 2rem;
    font-size: 1.3rem;
    font-weight: 700;
    border: none;
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
    margin-top: 2.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    position: relative;
    overflow: hidden;
}

.submit-deposit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.submit-deposit-btn:hover:not(:disabled) {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
}

.submit-deposit-btn:hover:not(:disabled)::before {
    left: 100%;
}

.submit-deposit-btn:active:not(:disabled) {
    transform: translateY(-2px) scale(1.01);
}

.submit-deposit-btn:disabled {
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

#amount-note {
    background: rgba(102, 126, 234, 0.1);
    border: 2px solid rgba(102, 126, 234, 0.2);
    color: #4338ca;
    padding: 1.2rem 1.5rem;
    border-radius: var(--border-radius-sm);
    font-size: 1rem;
    margin-top: 1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    backdrop-filter: blur(10px);
}

#amount-note i {
    font-size: 1.2rem;
    color: #667eea;
}

#details-generated-toast {
    background: var(--success-gradient);
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    margin: -1rem auto 2rem auto;
    text-align: center;
    opacity: 0;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateY(-30px) scale(0.9);
    max-width: max-content;
    box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
    backdrop-filter: blur(10px);
}

#details-generated-toast.show {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.toast-notification {
    position: fixed;
    top: 30px;
    right: 30px;
    background: var(--success-gradient);
    color: white;
    padding: 1.2rem 2rem;
    border-radius: var(--border-radius-sm);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    z-index: 1050;
    display: flex;
    align-items: center;
    gap: 15px;
    opacity: 0;
    visibility: hidden;
    transform: translateX(400px);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    max-width: 400px;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.toast-notification.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}

.toast-notification i {
    font-size: 1.5rem;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1040;
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.modal-content {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 3rem;
    border-radius: var(--border-radius);
    max-width: 600px;
    width: 90%;
    box-shadow: 0 30px 80px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-60px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 2rem;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 15px;
}

.modal-header i {
    background: var(--warning-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 1.5rem;
}

.modal-body p {
    margin-bottom: 1.5rem;
    line-height: 1.7;
    color: var(--gray);
    font-size: 1.1rem;
}

.modal-body strong {
    color: var(--dark);
    font-weight: 700;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 2rem;
    padding: 1.5rem;
    background: rgba(102, 126, 234, 0.05);
    border-radius: var(--border-radius-sm);
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.checkbox-container input[type="checkbox"] {
    width: 24px;
    height: 24px;
    cursor: pointer;
    accent-color: #667eea;
}

.checkbox-container label {
    font-weight: 600;
    cursor: pointer;
    margin: 0;
    font-size: 1.1rem;
    color: var(--dark);
}

.modal-footer {
    margin-top: 2.5rem;
    display: flex;
    justify-content: space-between;
    gap: 20px;
}

.btn {
    padding: 1rem 2rem;
    border: none;
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex: 1;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.btn-success {
    background: var(--success-gradient);
    color: white;
    box-shadow: 0 8px 25px rgba(17, 153, 142, 0.3);
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(17, 153, 142, 0.5);
}

.btn-success:disabled {
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    cursor: not-allowed;
    transform: none;
}

.btn-danger {
    background: var(--danger-gradient);
    color: white;
    box-shadow: 0 8px 25px rgba(250, 112, 154, 0.3);
}

.btn-danger:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(250, 112, 154, 0.5);
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
}

/* Responsive Design */
@media (max-width: 768px) {
    .deposit-container {
        margin: 1rem auto;
        padding: 0 15px;
    }
    
    .page-title {
        font-size: 2.5rem;
    }
    
    .page-subtitle {
        font-size: 1.1rem;
    }
    
    .deposit-form-card {
        padding: 2rem 1.5rem;
    }
    
    .form-section-title {
        font-size: 1.5rem;
    }
    
    .payment-method-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .payment-method-card {
        padding: 1.5rem 1rem;
        min-height: 120px;
    }
    
    .payment-method-card i {
        font-size: 2rem;
    }
    
    .payment-method-card .method-name {
        font-size: 0.9rem;
    }
    
    .wallet-info {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .copy-btn {
        width: 100%;
    }
    
    .bank-details li {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .upload-area {
        padding: 2rem 1.5rem;
    }
    
    .upload-area i {
        font-size: 2.5rem;
    }
    
    .summary-item {
        font-size: 1rem;
    }
    
    .summary-item.total {
        font-size: 1.4rem;
    }
    
    .submit-deposit-btn {
        font-size: 1.1rem;
        padding: 1.2rem;
    }
    
    .modal-content {
        padding: 2rem 1.5rem;
    }
    
    .modal-header {
        font-size: 1.5rem;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .toast-notification {
        right: 15px;
        left: 15px;
        max-width: none;
    }
}

@media (max-width: 480px) {
    .payment-method-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .page-title {
        font-size: 2rem;
    }
}

.divider {
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    margin: 2.5rem 0;
    border-radius: 1px;
}

/* Loading Animation */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

::-webkit-scrollbar-thumb {
    background: var(--primary-gradient);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
}
</style>

<div class="row">
    <div class="col-md-12 hidden-xs"><div id="acc_user_menu"><ul><?php require("inc/usermenu.inc.php"); ?></ul></div></div>
</div>

<div class="deposit-container">
    <div class="page-header">
        <h1 class="page-title">Fund Your Wallet</h1>
        <p class="page-subtitle">Choose a payment method to add funds to your account securely and instantly</p>
    </div>
    <div id="form-messages" class="text-center" style="margin-bottom: 1rem;"></div>

    <form id="deposit-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="paypal_agreement" id="paypal-agreement-hidden" value="no">

        <div class="deposit-form-card">
            <div class="form-section-title">
                <i class="fas fa-dollar-sign"></i>
                Enter Amount
            </div>
            <div class="form-group">
                <label for="amount">Amount to Deposit (USD)</label>
                <input type="number" name="amount" id="amount" class="form-control" placeholder="Enter amount (minimum $2.00)" min="2" max="100000" step="0.01" required>
                <div id="amount-note">
                    <i class="fas fa-info-circle"></i>
                    Please select a payment method to see the minimum deposit amount
                </div>
            </div>

            <div class="divider"></div>
            
            <div class="form-section-title">
                <i class="fas fa-credit-card"></i>
                Choose Payment Method
            </div>
            <div class="form-group">
                <label for="payment-method">Payment Method</label>
                <select name="payment_method" id="payment-method" class="form-select" required>
                    <option value="" disabled selected>-- Select a payment method --</option>
                    <option value="USDT TRC20">USDT TRC20</option>
                    <option value="Litecoin">Litecoin</option>
                    <option value="Bitcoin">Bitcoin</option>
                    <option value="Ghana Mobile Money">Ghana Mobile Money</option>
                    <option value="Nigeria (Fincra)">Nigeria Bank</option>
                    <option value="BinancePay">BinancePay</option>
                    <option value="Bybit">Bybit</option>
                    <option value="USA Bank">USA Bank Transfer</option>
                    <option value="RedotPay">RedotPay</option>
                    <option value="Payeer">Payeer</option>
                    <option value="PayPal">PayPal</option>
                </select>
            </div>
            
            <div id="details-generated-toast">
                <i class="fas fa-check-circle"></i>
                Payment Details Generated Successfully!
            </div>

            <div id="payment-details-USDTTRC20" class="payment-details-box">
                <h5><i class="fab fa-bitcoin"></i>USDT TRC20 Instructions</h5>
                <p>Send the exact total amount to the USDT TRC20 address below. Once complete, upload the transaction proof.</p>
                <div class="wallet-info">
                    <span>TF3JdNM3YTwJLaPrmFD7YsS6gBcVbde3Ww</span>
                    <button type="button" class="copy-btn" data-clipboard-text="TF3JdNM3YTwJLaPrmFD7YsS6gBcVbde3Ww">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            
            <div id="payment-details-Litecoin" class="payment-details-box">
                <h5><i class="fab fa-bitcoin"></i>Litecoin Instructions</h5>
                <p>Send the exact total amount to the Litecoin wallet below. Once complete, upload the transaction proof.</p>
                <div class="wallet-info">
                    <span>LhsriKrvRGif48En1JSMnj9N9bQ62LBvm9</span>
                    <button type="button" class="copy-btn" data-clipboard-text="LhsriKrvRGif48En1JSMnj9N9bQ62LBvm9">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            
            <div id="payment-details-Bitcoin" class="payment-details-box">
                <h5><i class="fab fa-bitcoin"></i>Bitcoin Instructions</h5>
                <p>Send the exact total amount to the Bitcoin wallet below. Once complete, upload the transaction proof.</p>
                <div class="wallet-info">
                    <span>1Jc7z4Lhy6d6rnNgbLPU7NYEc6uLjACaMu</span>
                    <button type="button" class="copy-btn" data-clipboard-text="1Jc7z4Lhy6d6rnNgbLPU7NYEc6uLjACaMu">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            
            <div id="payment-details-GhanaMobileMoney" class="payment-details-box">
                <h5><i class="fas fa-mobile-alt"></i>Ghana Mobile Money Details</h5>
                <ul class="bank-details">
                    <li>
                        <strong>MoMo ID:</strong> 
                        <div>
                            <span>816727</span> 
                            <button type="button" class="copy-btn" data-clipboard-text="816727">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </li>
                    <li><strong>MoMo Name:</strong> <span>CHINA iTECH</span></li>
                </ul>
                <h5 style="margin-top: 1.5rem;"><i class="fas fa-list-ol"></i>Instructions</h5>
                <ol style="padding-left: 1.5rem; line-height: 1.8;">
                    <li>Dial <b>*170#</b></li>
                    <li>Select option <b>2</b> (MoMoPay & Pay Bill)</li>
                    <li>Choose option <b>1</b> (MoMoPay)</li>
                    <li>Enter MoMo ID: <b>816727</b></li>
                    <li>Follow the prompts. When you are done, upload your Payment Proof below.</li>
                </ol>
            </div>

            <div id="payment-details-NigeriaFincra" class="payment-details-box">
                <h5><i class="fas fa-university"></i>Nigeria Bank Instructions</h5>
                <p>Pay the exact total amount by clicking the Pay with Fincra button below. After payment, upload your proof.</p>
                <button type="button" class="btn btn-primary" id="fincra-pay-btn">
                    <i class="fas fa-credit-card"></i> Pay with Fincra
                </button>
            </div>
            
            <div id="payment-details-BinancePay" class="payment-details-box">
                <h5><i class="fab fa-bitcoin"></i>BinancePay Instructions</h5>
                <p>Send the exact total amount to the BinancePay ID below. Once complete, upload the transaction proof.</p>
                <div class="wallet-info">
                    <span>755483459</span>
                    <button type="button" class="copy-btn" data-clipboard-text="755483459">
                        <i class="fas fa-copy"></i> Copy ID
                    </button>
                </div>
            </div>
            
            <div id="payment-details-Bybit" class="payment-details-box">
                <h5><i class="fab fa-bitcoin"></i>Bybit Instructions</h5>
                <p>Send the exact total amount to the Bybit ID below. Once complete, upload the transaction proof.</p>
                <div class="wallet-info">
                    <span>66509337</span>
                    <button type="button" class="copy-btn" data-clipboard-text="66509337">
                        <i class="fas fa-copy"></i> Copy ID
                    </button>
                </div>
            </div>
            
            <div id="payment-details-USABank" class="payment-details-box">
                <h5><i class="fas fa-university"></i>USA Bank Transfer Instructions</h5>
                <p>Send the exact total amount to the bank account below. Once complete, upload the transaction proof.</p>
                <ul class="bank-details">
                    <li><strong>Account Name:</strong> <span>Mitichuell Myles</span></li>
                    <li><strong>Bank Name:</strong> <span>Pathward, N.A.</span></li>
                    <li>
                        <strong>Account Number:</strong> 
                        <div>
                            <span>3290216535090</span> 
                            <button type="button" class="copy-btn" data-clipboard-text="3290216535090">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </li>
                    <li>
                        <strong>Routing Number:</strong> 
                        <div>
                            <span>273070278</span> 
                            <button type="button" class="copy-btn" data-clipboard-text="273070278">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
            
            <div id="payment-details-RedotPay" class="payment-details-box">
                <h5><i class="fas fa-wallet"></i>RedotPay Instructions</h5>
                <p>Send the exact total amount to the RedotPay ID below. Once complete, upload the transaction proof.</p>
                <div class="wallet-info">
                    <span>1092656172</span>
                    <button type="button" class="copy-btn" data-clipboard-text="1092656172">
                        <i class="fas fa-copy"></i> Copy ID
                    </button>
                </div>
            </div>
            
            <div id="payment-details-Payeer" class="payment-details-box">
                <h5><i class="fas fa-wallet"></i>Payeer Instructions</h5>
                <p>Send the exact total amount to the Payeer wallet below. Once complete, upload the transaction proof.</p>
                <div class="wallet-info">
                    <span>P1096095443</span>
                    <button type="button" class="copy-btn" data-clipboard-text="P1096095443">
                        <i class="fas fa-copy"></i> Copy ID
                    </button>
                </div>
            </div>
            
            <div id="payment-details-PayPal" class="payment-details-box">
                <h5><i class="fab fa-paypal"></i>PayPal Instructions</h5>
                <p>Send the exact total amount to the PayPal account below. Once complete, upload a screenshot as proof.</p>
                <div style="margin-bottom: 1rem;">
                    <strong>ACCOUNT NAME:</strong> CHINA iTECH
                </div>
                <div class="wallet-info">
                    <span>estherabandohs@gmail.com</span>
                    <button type="button" class="copy-btn" data-clipboard-text="estherabandohs@gmail.com">
                        <i class="fas fa-copy"></i> Copy Email
                    </button>
                </div>
                <div style="margin-top: 1rem;">
                    <strong>PayPal Description:</strong>
                </div>
                <div class="wallet-info">
                    <span>For Graphics Design Work</span>
                    <button type="button" class="copy-btn" data-clipboard-text="For Graphics Design Work">
                        <i class="fas fa-copy"></i> Copy Note
                    </button>
                </div>
            </div>

            <div class="form-group" id="proof-upload-section" style="margin-top: 2rem; display: none;">
                <label for="proof-image">Upload Payment Proof (Required)</label>
                <div class="upload-area" onclick="document.getElementById('proof-image-input').click();">
                    <input type="file" name="proof_image" id="proof-image-input" accept="image/*" style="display: none;">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span id="upload-label">Click to Attach Screenshot</span>
                </div>
                <img id="proof-preview" src="#" alt="Proof Preview" />
            </div>

            <div class="summary-box">
                <div class="summary-item">
                    <span class="label">Deposit Amount:</span>
                    <span class="value" id="summary-amount">$0.00</span>
                </div>
                <div class="summary-item">
                    <span class="label">Processing Fee:</span>
                    <span class="value" id="summary-fee">$0.00</span>
                </div>
                <div class="summary-item" id="summary-converted-amount" style="display: none;">
                    <span class="label">You Will Pay (Approx.):</span>
                    <span class="value" id="summary-converted-value">--</span>
                </div>
                <div class="summary-item total">
                    <strong class="label">Total to Pay:</strong>
                    <strong id="summary-total">$0.00</strong>
                </div>
            </div>

            <div class="form-group" style="margin-top: 2.5rem;">
                <button type="submit" id="submit-deposit-btn" class="submit-deposit-btn">
                    <i class="fas fa-arrow-right"></i> Proceed with Deposit
                </button>
            </div>
        </div>
    </form>
</div>

<div id="paypal-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle"></i>
            PayPal Deposit Agreement
        </div>
        <div class="modal-body">
            <p>By depositing via PayPal, you agree that these funds are for <strong>shopping and services only</strong> on our platform. You are not allowed to withdraw them.</p>

            <p>For your safety, please make sure to send payment directly from your own PayPal account. We do not accept payments from third parties.</p>

            <p>Do not use our PayPal email to receive money from others. If you do this, we will block the funds and suspend your account.</p>
               
            <p>If you want to withdraw your PayPal funds? Please go to our <a href="/start.php" target="_blank" class="btn btn-success" style="display: inline-block; padding: 0.5rem 1rem; margin: 0.5rem 0; text-decoration: none;">Exchange service</a> page. Do not use this deposit page.</p>

            <div class="checkbox-container">
                <input type="checkbox" id="paypal-agree-checkbox">
                <label for="paypal-agree-checkbox">I understand and agree to these terms.</label>
            </div>
        </div>
        <div class="modal-footer">
            <button id="paypal-cancel-btn" class="btn btn-danger">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button id="paypal-confirm-btn" class="btn btn-success" disabled>
                <i class="fas fa-check"></i> Confirm & Continue
            </button>
        </div>
    </div>
</div>

<div id="toast-notification" class="toast-notification">
    <span id="toast-icon">✔️</span>
    <span id="toast-message"></span>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = {
        'USDT TRC20': { fee: 1.00, min: 2.00, max: 100000.00, icon: 'fab fa-bitcoin' },
        'Litecoin': { fee: 1.00, min: 2.00, max: 100000.00, icon: 'fab fa-bitcoin' },
        'Bitcoin': { fee: 1.00, min: 2.00, max: 100000.00, icon: 'fab fa-bitcoin' },
        'Ghana Mobile Money': { fee: 1.00, min: 2.00, max: 100000.00, localCurrency: { rate: 13.5, code: 'GHS' }, icon: 'fas fa-mobile-alt' },
        'Nigeria (Fincra)': { fee: 1.00, min: 2.00, max: 100000.00, localCurrency: { rate: 1700, code: 'NGN' }, icon: 'fas fa-university' },
        'BinancePay': { fee: 1.00, min: 2.00, max: 100000.00, icon: 'fab fa-bitcoin' },
        'Bybit': { fee: 1.00, min: 2.00, max: 100000.00, icon: 'fab fa-bitcoin' },
        'USA Bank': { fee: 10.00, min: 50.00, max: 100000.00, icon: 'fas fa-university' },
        'RedotPay': { fee: 3.00, min: 5.00, max: 100000.00, icon: 'fas fa-wallet' },
        'Payeer': { fee: 3.00, min: 10.00, max: 100000.00, icon: 'fas fa-wallet' },
        'PayPal': { fee: 3.00, min: 20.00, max: 100000.00, requiresAgreement: true, icon: 'fab fa-paypal' }
    };

    const depositForm = document.getElementById('deposit-form');
    const amountInput = document.getElementById('amount');
    const amountNote = document.getElementById('amount-note');
    const paymentMethodSelect = document.getElementById('payment-method');
    const proofUploadSection = document.getElementById('proof-upload-section');
    const proofImageInput = document.getElementById('proof-image-input');
    const submitButton = document.getElementById('submit-deposit-btn');
    const summaryAmount = document.getElementById('summary-amount');
    const summaryFee = document.getElementById('summary-fee');
    const summaryTotal = document.getElementById('summary-total');
    const summaryConvertedRow = document.getElementById('summary-converted-amount');
    const summaryConvertedValue = document.getElementById('summary-converted-value');
    const paypalModal = document.getElementById('paypal-modal');
    const paypalAgreeCheckbox = document.getElementById('paypal-agree-checkbox');
    const paypalConfirmBtn = document.getElementById('paypal-confirm-btn');
    const paypalCancelBtn = document.getElementById('paypal-cancel-btn');
    const paypalAgreementHidden = document.getElementById('paypal-agreement-hidden');
    const detailsToast = document.getElementById('details-generated-toast');
    const fincraPayBtn = document.getElementById('fincra-pay-btn');

    function showDetailsToast() {
        detailsToast.classList.add('show');
        setTimeout(() => {
            detailsToast.classList.remove('show');
        }, 3000);
    }

    function updateSummaryAndValidation() {
        const selectedMethod = paymentMethodSelect.value;
        const methodDetails = paymentMethods[selectedMethod] || { fee: 0, min: 2, max: 100000 };
        const amount = parseFloat(amountInput.value) || 0;
        const fee = methodDetails.fee;
        const total = amount > 0 ? amount + fee : 0;

        summaryAmount.textContent = `$${amount.toFixed(2)}`;
        summaryFee.textContent = `$${fee.toFixed(2)}`;
        summaryTotal.innerHTML = `<strong>$${total.toFixed(2)}</strong>`;
        amountInput.min = methodDetails.min;
        amountInput.max = methodDetails.max;
        amountInput.placeholder = `Enter amount (minimum $${methodDetails.min.toFixed(2)})`;

        if (methodDetails.localCurrency && total > 0) {
            const localTotal = total * methodDetails.localCurrency.rate;
            summaryConvertedValue.textContent = `${localTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${methodDetails.localCurrency.code}`;
            summaryConvertedRow.style.display = 'flex';
        } else {
            summaryConvertedRow.style.display = 'none';
        }

        submitButton.disabled = amount > 0 && (amount < methodDetails.min || amount > methodDetails.max);
    }

    function handlePaymentMethodChange() {
        const selectedMethod = paymentMethodSelect.value;
        document.querySelectorAll('.payment-details-box').forEach(box => box.style.display = 'none');
        
        if (selectedMethod) {
            showDetailsToast();
            const idSelector = `payment-details-${selectedMethod.replace(/[()\s]/g, '')}`;
            const detailBox = document.getElementById(idSelector);
            const methodDetails = paymentMethods[selectedMethod];
            
            if(detailBox) {
                detailBox.style.display = 'block';
            }
            
            if (methodDetails) {
                amountNote.innerHTML = `
                    <i class="fas fa-info-circle"></i>
                    Minimum deposit for ${selectedMethod} is $${methodDetails.min.toFixed(2)}. Processing fee: $${methodDetails.fee.toFixed(2)}.
                `;
            }

            proofUploadSection.style.display = 'block';
            proofImageInput.required = true;

            if (methodDetails?.requiresAgreement) {
                paypalModal.style.display = 'flex';
            }
        } else {
            proofUploadSection.style.display = 'none';
            proofImageInput.required = false;
            amountNote.innerHTML = `
                <i class="fas fa-info-circle"></i>
                Please select a payment method to see the minimum deposit amount
            `;
        }

        paypalAgreementHidden.value = 'no';
        paypalAgreeCheckbox.checked = false;
        paypalConfirmBtn.disabled = true;
        updateSummaryAndValidation();
    }

    amountInput.addEventListener('input', updateSummaryAndValidation);
    paymentMethodSelect.addEventListener('change', handlePaymentMethodChange);

    paypalAgreeCheckbox.addEventListener('change', () => { 
        paypalConfirmBtn.disabled = !paypalAgreeCheckbox.checked; 
    });
    
    paypalConfirmBtn.addEventListener('click', () => {
        paypalAgreementHidden.value = 'yes';
        paypalModal.style.display = 'none';
        showToast('Agreement confirmed. You can now proceed.');
    });
    
    paypalCancelBtn.addEventListener('click', () => {
        paymentMethodSelect.value = '';
        handlePaymentMethodChange();
        paypalModal.style.display = 'none';
        showToast('PayPal deposit cancelled.', true);
    });

    proofImageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('proof-preview').src = e.target.result;
                document.getElementById('proof-preview').style.display = 'block';
                document.getElementById('upload-label').textContent = this.files[0].name;
                document.querySelector('.upload-area').classList.add('has-file');
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    fincraPayBtn.addEventListener('click', function() {
        const url = 'https://checkout.fincra.com/payment-link/ngns';
        const windowName = 'fincraPopup';
        const width = 600, height = 750;
        const left = (screen.width / 2) - (width / 2);
        const top = (screen.height / 2) - (height / 2);
        const windowFeatures = `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,resizable=yes`;
        window.open(url, windowName, windowFeatures);
    });

    depositForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const selectedMethod = paymentMethodSelect.value;
        if (!selectedMethod) {
            showToast('Please select a payment method.', true);
            return;
        }

        if (proofImageInput.files.length === 0) {
            showToast('Please upload a screenshot of your payment for review.', true);
            return;
        }

        const methodDetails = paymentMethods[selectedMethod];
        if (methodDetails.requiresAgreement && paypalAgreementHidden.value !== 'yes') {
            showToast('You must agree to the PayPal terms to proceed.', true);
            paypalModal.style.display = 'flex';
            return;
        }
        
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="loading-spinner"></span> Processing...';
        submitButton.disabled = true;

        const formData = new FormData(depositForm);
        fetch('deposit_handler.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message);
                    depositForm.reset();
                    handlePaymentMethodChange();
                    document.getElementById('proof-preview').style.display = 'none';
                    document.getElementById('upload-label').textContent = 'Click to Attach Screenshot';
                    document.querySelector('.upload-area').classList.remove('has-file');
                } else {
                    showToast(data.message, true);
                }
            })
            .catch(error => {
                console.error('Submission Error:', error);
                showToast('An unexpected network error occurred.', true);
            })
            .finally(() => {
                submitButton.innerHTML = originalButtonText;
                updateSummaryAndValidation();
            });
    });

    new ClipboardJS('.copy-btn').on('success', function(e) {
        showToast('Copied to clipboard!');
        e.clearSelection();
    });
});

let toastTimeout;
function showToast(message, isError = false) {
    const toast = document.getElementById('toast-notification');
    if (!toast) return;
    const toastMessage = toast.querySelector('#toast-message');
    const toastIcon = toast.querySelector('#toast-icon');
    
    toastMessage.textContent = message;
    toastIcon.textContent = isError ? '❌' : '✅';
    toast.style.background = isError ? 'var(--danger-gradient)' : 'var(--success-gradient)';
    toast.classList.add('show');
    
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => { 
        toast.classList.remove('show'); 
    }, 5000);
}
</script>

<?php require_once("inc/footer.inc.php"); ?>