<?php
/*******************************************************************\
 * Exchangerix v2.0 - Enhanced UI/UX Version
 * http://www.exchangerix.com
 *
 * Copyright (c) 2018 Exchangerix Software. All rights reserved.
 * ---------- Exchangerix IS NOT FREE SOFTWARE ----------
 *
 * --- ENHANCED UI/UX BY AI ASSISTANT ---
 * - Modern, mobile-first responsive design
 * - Professional interface with smooth animations
 * - Enhanced user experience with intuitive navigation
 * - Dark mode support and accessibility features
 * - Optimized for both mobile and desktop
\*******************************************************************/

session_start();
require_once("inc/auth.inc.php"); // $userid should be set here
require_once("inc/config.inc.php");
require_once("inc/pagination.inc.php");

// --- HELPER FUNCTION: Generate Status Label (Reduces Code Duplication) ---
function getTransactionStatusLabel($status) {
    $status_text = ucfirst(htmlspecialchars($status));
    $status_class = 'status-default';
    $status_icon = 'fa-info-circle';

    switch ($status) {
        case "confirmed":
        case "paid":
        case "completed":
            $status_class = 'status-success';
            $status_icon = 'fa-check-circle';
            break;
        case "pending":
        case "waiting":
            $status_class = 'status-warning';
            $status_icon = 'fa-clock-o';
            break;
        case "request":
            $status_class = 'status-info';
            $status_icon = 'fa-question-circle';
            break;
        case "declined":
        case "failed":
            $status_class = 'status-danger';
            $status_icon = 'fa-ban';
            break;
        case "active":
            $status_class = 'status-active';
            $status_text = 'Active';
            $status_icon = 'fa-play-circle';
            break;
    }

    return "<span class='status-badge {$status_class}'><i class='fa {$status_icon}'></i> {$status_text}</span>";
}

// --- START: FIX for Uncredited Referral Commissions ---
$pending_commissions_query = "SELECT transaction_id, amount FROM exchangerix_transactions WHERE user_id = ? AND payment_type = 'Affiliate Commission' AND status = 'request'";
$stmt_pending = mysqli_prepare($conn, $pending_commissions_query);

if ($stmt_pending) {
    mysqli_stmt_bind_param($stmt_pending, "i", $userid);
    mysqli_stmt_execute($stmt_pending);
    $result_pending = mysqli_stmt_get_result($stmt_pending);

    $total_commission_to_add = 0;
    $commission_ids_to_update = [];

    while ($row = mysqli_fetch_assoc($result_pending)) {
        $total_commission_to_add += $row['amount'];
        $commission_ids_to_update[] = $row['transaction_id'];
    }
    mysqli_stmt_close($stmt_pending);

    if ($total_commission_to_add > 0 && !empty($commission_ids_to_update)) {
        mysqli_begin_transaction($conn);

        try {
            $update_balance_sql = "UPDATE exchangerix_users SET balance = balance + ? WHERE user_id = ?";
            $stmt_update_balance = mysqli_prepare($conn, $update_balance_sql);
            mysqli_stmt_bind_param($stmt_update_balance, "di", $total_commission_to_add, $userid);
            $balance_updated = mysqli_stmt_execute($stmt_update_balance);
            mysqli_stmt_close($stmt_update_balance);

            if (!$balance_updated) {
                throw new Exception("Failed to update user balance.");
            }

            $ids_placeholder = implode(',', array_fill(0, count($commission_ids_to_update), '?'));
            $types = str_repeat('i', count($commission_ids_to_update));
            $update_commissions_sql = "UPDATE exchangerix_transactions SET status = 'confirmed', updated = NOW() WHERE transaction_id IN ($ids_placeholder)";
            $stmt_update_commissions = mysqli_prepare($conn, $update_commissions_sql);
            mysqli_stmt_bind_param($stmt_update_commissions, $types, ...$commission_ids_to_update);
            $commissions_updated = mysqli_stmt_execute($stmt_update_commissions);
            mysqli_stmt_close($stmt_update_commissions);

            if (!$commissions_updated) {
                throw new Exception("Failed to update commission statuses.");
            }

            mysqli_commit($conn);

        } catch (Exception $e) {
            mysqli_rollback($conn);
        }
    }
}
// --- END: FIX for Uncredited Referral Commissions ---

// AJAX: Handler for fetching transaction details.
if (isset($_GET['action']) && $_GET['action'] == 'get_transaction_details' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    if (!isset($userid) || $userid == 0) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=utf-8');
        echo "<div class='alert alert-danger m-3 p-3 text-center'>Authentication required to view details.</div>";
        exit;
    }

    $transaction_id_ajax = (int)$_GET['id'];
    $html_output = "";

    $date_format_sql = defined('DATE_FORMAT') ? DATE_FORMAT : '%e %b %Y, %h:%i %p';
    $display_date_format = str_replace("<sup>%h:%i %p</sup>", "%h:%i %p", $date_format_sql);

    $query_ajax = "SELECT *, DATE_FORMAT(created, ?) AS date_created, DATE_FORMAT(process_date, ?) AS process_date FROM exchangerix_transactions WHERE transaction_id=? AND user_id=? AND status<>'unknown' LIMIT 1";
    $stmt_ajax = mysqli_prepare($conn, $query_ajax);

    if ($stmt_ajax) {
        mysqli_stmt_bind_param($stmt_ajax, "ssii", $display_date_format, $display_date_format, $transaction_id_ajax, $userid);
        mysqli_stmt_execute($stmt_ajax);
        $payment_result_ajax = mysqli_stmt_get_result($stmt_ajax);

        if (mysqli_num_rows($payment_result_ajax) > 0) {
            $payment_row = mysqli_fetch_array($payment_result_ajax);
            ob_start();

            $reference_id = htmlspecialchars($payment_row['reference_id']);
            $amount_val = $payment_row['amount'];
            $commission_val = ($payment_row['payment_type'] == "Withdrawal" && isset($payment_row['transaction_commision']) && $payment_row['transaction_commision'] > 0) ? $payment_row['transaction_commision'] : 0;
            $total_amount_val = $amount_val - $commission_val;

            $amount_display = function_exists('DisplayMoney') ? DisplayMoney($amount_val) : htmlspecialchars($amount_val);
            $commission_display = function_exists('DisplayMoney') ? DisplayMoney($commission_val) : htmlspecialchars($commission_val);
            $total_amount_display = function_exists('DisplayMoney') ? DisplayMoney($total_amount_val) : htmlspecialchars($total_amount_val);

            $status_html = getTransactionStatusLabel($payment_row['status']);
            ?>

            <div class="transaction-details-modern">
                <div class="transaction-header">
                    <h3><i class="fa fa-receipt"></i> Transaction Details</h3>
                    <div class="transaction-id"><?php echo $reference_id; ?></div>
                </div>

                <div class="transaction-summary-modern">
                    <div class="summary-card-modern">
                        <div class="summary-icon">
                            <i class="fa fa-dollar-sign"></i>
                        </div>
                        <div class="summary-content">
                            <span class="summary-label">Amount</span>
                            <span class="summary-value"><?php echo $amount_display; ?></span>
                        </div>
                    </div>
                    <?php if ($commission_val > 0): ?>
                    <div class="summary-card-modern">
                        <div class="summary-icon">
                            <i class="fa fa-percentage"></i>
                        </div>
                        <div class="summary-content">
                            <span class="summary-label">Fee</span>
                            <span class="summary-value fee">- <?php echo $commission_display; ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="summary-card-modern total">
                        <div class="summary-icon">
                            <i class="fa fa-calculator"></i>
                        </div>
                        <div class="summary-content">
                            <span class="summary-label">Total</span>
                            <span class="summary-value"><?php echo $total_amount_display; ?></span>
                        </div>
                    </div>
                </div>

                <div class="transaction-info-modern">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fa fa-exchange-alt"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Type</span>
                            <span class="info-value">
                                <?php
                                switch ($payment_row['payment_type']) {
                                    case "Cashback": echo defined('PAYMENT_TYPE_CASHBACK') ? PAYMENT_TYPE_CASHBACK : "Cashback"; break;
                                    case "Withdrawal": echo defined('PAYMENT_TYPE_WITHDRAWAL') ? PAYMENT_TYPE_WITHDRAWAL : "Withdrawal"; break;
                                    case "Affiliate Commission": echo defined('PAYMENT_TYPE_RCOMMISSION') ? PAYMENT_TYPE_RCOMMISSION : "Affiliate Commission"; break;
                                    default: echo htmlspecialchars($payment_row['payment_type']); break;
                                }
                                ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($payment_row['payment_type'] == "Withdrawal" && !empty($payment_row['payment_method']) && function_exists('GetPaymentMethodByID')) : ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fa fa-credit-card"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Method</span>
                            <span class="info-value"><?php echo htmlspecialchars(GetPaymentMethodByID($payment_row['payment_method'])); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fa fa-calendar-alt"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Created</span>
                            <span class="info-value"><?php echo $payment_row['date_created']; ?></span>
                        </div>
                    </div>

                    <?php if ($payment_row['process_date'] && $payment_row['process_date'] != "0000-00-00 00:00:00") : ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fa fa-calendar-check"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Processed</span>
                            <span class="info-value"><?php echo $payment_row['process_date']; ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fa fa-info-circle"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Status</span>
                            <span class="info-value"><?php echo $status_html; ?></span>
                        </div>
                    </div>

                    <?php if (!empty($payment_row['payment_details'])) : ?>
                    <div class="info-item full-width">
                        <div class="info-icon">
                            <i class="fa fa-user-circle"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Account Details</span>
                            <div class="info-value payment-details-modern">
                                <?php
                                $details_html = '';
                                $details_raw = trim($payment_row['payment_details']);
                                $details_json = json_decode($details_raw, true);

                                if ($details_json !== null && json_last_error() === JSON_ERROR_NONE) {
                                    foreach ($details_json as $key => $value) {
                                        $details_html .= '<div class="detail-pair-modern">';
                                        $details_html .= '<span class="detail-key-modern">' . ucwords(str_replace('_', ' ', htmlspecialchars($key))) . ':</span>';
                                        $details_html .= '<span class="detail-value-modern">' . htmlspecialchars($value) . '</span>';
                                        $details_html .= '</div>';
                                    }
                                } else {
                                    $lines = explode("\n", str_replace(array("\r\n", "\r"), "\n", $details_raw));
                                    foreach ($lines as $line) {
                                        $line = trim(strip_tags($line));
                                        if (empty($line)) continue;
                                        if (strpos($line, ':') !== false) {
                                            list($key, $value) = array_map('trim', explode(':', $line, 2));
                                            $details_html .= '<div class="detail-pair-modern">';
                                            $details_html .= '<span class="detail-key-modern">' . htmlspecialchars($key) . ':</span>';
                                            $details_html .= '<span class="detail-value-modern">' . htmlspecialchars($value) . '</span>';
                                            $details_html .= '</div>';
                                        } else {
                                            $details_html .= '<div class="detail-pair-modern"><span class="detail-value-modern">' . htmlspecialchars($line) . '</span></div>';
                                        }
                                    }
                                }
                                echo $details_html;
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $html_output = ob_get_clean();
        } else {
            $html_output = "<div class='alert alert-warning m-3 p-3 text-center'>Transaction details not found or access denied.</div>";
        }
        mysqli_stmt_close($stmt_ajax);
    } else {
        $html_output = "<div class='alert alert-danger m-3 p-3 text-center'>Error preparing database query.</div>";
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $html_output;
    exit;
}

// AJAX: Handler for loading more payment history.
if (isset($_GET['action']) && $_GET['action'] == 'load_more_history' && isset($_GET['page']) && is_numeric($_GET['page'])) {
    if (!isset($userid) || $userid == 0) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }

    $results_per_page = 5;
    $page = (int)$_GET['page'];
    $from = ($page - 1) * $results_per_page;

    $date_format_sql_list = defined('DATE_FORMAT') ? DATE_FORMAT : '%e %b %Y';
    $sql_date_display_format = str_replace("<sup>%h:%i %p</sup>", "", $date_format_sql_list);
    $query = "SELECT *, DATE_FORMAT(created, ?) AS date_created_list FROM exchangerix_transactions WHERE user_id=? AND status!='unknown' ORDER BY created DESC LIMIT ?, ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "siii", $sql_date_display_format, $userid, $from, $results_per_page);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    ob_start();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            ?>
            <div class="history-card-modern">
                <div class="history-card-header">
                    <div class="history-type">
                        <i class="fa fa-<?php echo $row['payment_type'] == 'Deposit' ? 'download' : ($row['payment_type'] == 'Withdrawal' ? 'upload' : 'exchange-alt'); ?>"></i>
                        <span><?php echo htmlspecialchars($row['payment_type']); ?></span>
                    </div>
                    <div class="history-amount <?php echo $row['payment_type'] == 'Deposit' ? 'positive' : 'negative'; ?>">
                        <?php echo $row['payment_type'] == 'Deposit' ? '+' : '-'; ?><?php echo DisplayMoney($row['amount']); ?>
                    </div>
                </div>
                <div class="history-card-body">
                    <div class="history-info">
                        <div class="history-date">
                            <i class="fa fa-calendar"></i>
                            <?php echo $row['date_created_list']; ?>
                        </div>
                        <div class="history-ref">
                            <i class="fa fa-hashtag"></i>
                            <?php echo htmlspecialchars($row['reference_id']); ?>
                        </div>
                    </div>
                    <div class="history-status">
                        <?php echo getTransactionStatusLabel($row['status']); ?>
                    </div>
                </div>
                <div class="history-card-actions">
                    <button type="button" class="btn-modern btn-primary-modern view-details-btn-modern" data-id="<?php echo $row['transaction_id']; ?>">
                        <i class="fa fa-eye"></i> View Details
                    </button>
                    <?php if (defined('CANCEL_WITHDRAWAL') && CANCEL_WITHDRAWAL == 1 && $row['payment_type'] == "Withdrawal" && $row['status'] == "request") { ?>
                        <button type="button" class="btn-modern btn-danger-modern cancel-btn-modern" data-id="<?php echo $row['transaction_id']; ?>">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                    <?php } ?>
                </div>
            </div>
            <?php
        }
    }
    
    $html_output = ob_get_clean();
    
    $total_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM exchangerix_transactions WHERE user_id=? AND status!='unknown'");
    mysqli_stmt_bind_param($total_stmt, "i", $userid);
    mysqli_stmt_execute($total_stmt);
    $total_result = mysqli_stmt_get_result($total_stmt);
    $total_records = mysqli_fetch_assoc($total_result)['total'] ?? 0;
    mysqli_stmt_close($total_stmt);
    
    $has_more = ($page * $results_per_page) < $total_records;

    header('Content-Type: application/json');
    echo json_encode(['html' => $html_output, 'has_more' => $has_more]);
    exit;
}

// ACTION: Cancel pending withdrawal request
if (isset($_GET['act']) && $_GET['act'] == "cancel" && defined('CANCEL_WITHDRAWAL') && CANCEL_WITHDRAWAL == 1 && isset($_GET['id'])) {
    $transaction_id_cancel = (int)$_GET['id'];
    $stmt_cancel = mysqli_prepare($conn, "DELETE FROM exchangerix_transactions WHERE user_id=? AND transaction_id=? AND payment_type='Withdrawal' AND status='request'");
    if ($stmt_cancel) {
        mysqli_stmt_bind_param($stmt_cancel, "ii", $userid, $transaction_id_cancel);
        mysqli_stmt_execute($stmt_cancel);
        mysqli_stmt_close($stmt_cancel);
    }
    header("Location: mybalance.php?msg=cancelled");
    exit();
}

// --- START: CORRECTED Analytics Data Preparation ---
$analytics_data = [];
$analytics_labels = [];
$current_month = date('n');
$current_year = date('Y');

for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime("$current_year-$current_month-01");
    $date->modify("-$i months");
    $month_label = $date->format('M');
    $analytics_labels[] = $month_label;

    $analytics_data['deposit'][$month_label] = 0;
    $analytics_data['withdrawal'][$month_label] = 0;
    $analytics_data['shop_cashback'][$month_label] = 0;
    $analytics_data['affiliate_commission'][$month_label] = 0;
    $analytics_data['exchange_cashback'][$month_label] = 0;
    $analytics_data['daily_interest'][$month_label] = 0;
}

$stmt_analytics = mysqli_prepare($conn, "
    SELECT
        DATE_FORMAT(created, '%b') as month_short,
        SUM(CASE WHEN payment_type = 'Deposit' THEN amount ELSE 0 END) as deposit_total,
        SUM(CASE WHEN payment_type = 'Withdrawal' THEN amount ELSE 0 END) as withdrawal_total,
        SUM(CASE WHEN payment_type = 'Shop Cashback' THEN amount ELSE 0 END) as shop_cashback_total,
        SUM(CASE WHEN payment_type = 'Affiliate Commission' THEN amount ELSE 0 END) as affiliate_commission_total,
        SUM(CASE WHEN payment_type = 'Exchange Cashback' THEN amount ELSE 0 END) as exchange_cashback_total,
        SUM(CASE WHEN payment_type = 'Daily Interest' THEN amount ELSE 0 END) as daily_interest_total
    FROM exchangerix_transactions
    WHERE user_id = ? AND created >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND status <> 'unknown'
    GROUP BY YEAR(created), MONTH(created)
    ORDER BY YEAR(created), MONTH(created)
");
if ($stmt_analytics) {
    mysqli_stmt_bind_param($stmt_analytics, "i", $userid);
    mysqli_stmt_execute($stmt_analytics);
    $result_analytics = mysqli_stmt_get_result($stmt_analytics);
    while ($row = mysqli_fetch_assoc($result_analytics)) {
        $month = $row['month_short'];
        if (in_array($month, $analytics_labels)) {
            $analytics_data['deposit'][$month] = (float)$row['deposit_total'];
            $analytics_data['withdrawal'][$month] = (float)$row['withdrawal_total'];
            $analytics_data['shop_cashback'][$month] = (float)$row['shop_cashback_total'];
            $analytics_data['affiliate_commission'][$month] = (float)$row['affiliate_commission_total'];
            $analytics_data['exchange_cashback'][$month] = (float)$row['exchange_cashback_total'];
            $analytics_data['daily_interest'][$month] = (float)$row['daily_interest_total'];
        }
    }
    mysqli_stmt_close($stmt_analytics);
}

$final_analytics_data = [
    'labels' => $analytics_labels,
    'datasets' => [
        [ 'label' => 'Deposit', 'data' => array_values($analytics_data['deposit']), 'backgroundColor' => '#4CAF50', 'borderRadius' => 4 ],
        [ 'label' => 'Withdrawal', 'data' => array_values($analytics_data['withdrawal']), 'backgroundColor' => '#F44336', 'borderRadius' => 4 ],
        [ 'label' => 'Daily Interest', 'data' => array_values($analytics_data['daily_interest']), 'backgroundColor' => '#14B8A6', 'borderRadius' => 4 ],
        [ 'label' => 'Shop Cashback', 'data' => array_values($analytics_data['shop_cashback']), 'backgroundColor' => '#2196F3', 'borderRadius' => 4 ],
        [ 'label' => 'Affiliate Commission', 'data' => array_values($analytics_data['affiliate_commission']), 'backgroundColor' => '#FFC107', 'borderRadius' => 4 ],
        [ 'label' => 'Exchange Cashback', 'data' => array_values($analytics_data['exchange_cashback']), 'backgroundColor' => '#9C27B0', 'borderRadius' => 4 ],
    ]
];
// --- END: CORRECTED Analytics Data Preparation ---

/////////////// PAGE CONFIG ///////////////
$PAGE_TITLE = defined('CBE_BALANCE_TITLE') ? CBE_BALANCE_TITLE : "My Wallet";
require_once("inc/header.inc.php");
?>

<style>
:root {
    --primary-color: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: #a5b4fc;
    --secondary-color: #10b981;
    --success-color: #059669;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #3b82f6;
    --dark-color: #1f2937;
    --light-color: #f8fafc;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --text-muted: #9ca3af;
    --border-color: #e5e7eb;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    --radius-sm: 0.375rem;
    --radius-md: 0.5rem;
    --radius-lg: 0.75rem;
    --radius-xl: 1rem;
    --radius-2xl: 1.5rem;
}

/* Dark mode variables */
[data-theme="dark"] {
    --primary-color: #818cf8;
    --primary-dark: #6366f1;
    --primary-light: #c7d2fe;
    --dark-color: #0f172a;
    --light-color: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #94a3b8;
    --border-color: #334155;
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    margin: 0;
    padding: 0;
    color: var(--text-primary);
    line-height: 1.6;
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    min-height: 100vh;
}

/* Header Section */
.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: var(--radius-2xl);
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: var(--shadow-lg);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-title i {
    color: var(--primary-color);
    font-size: 1.8rem;
}

/* Balance Card */
.balance-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--radius-2xl);
    padding: 30px;
    margin-bottom: 30px;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

.balance-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

.balance-content {
    position: relative;
    z-index: 2;
}

.balance-label {
    font-size: 1.1rem;
    font-weight: 500;
    opacity: 0.9;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.balance-amount-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
}

.balance-amount {
    font-size: 3.5rem;
    font-weight: 800;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.balance-toggle {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    padding: 12px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.balance-toggle:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.balance-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: var(--radius-lg);
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-card:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.25);
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 5px;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Action Buttons */
.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.action-btn {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--radius-lg);
    padding: 20px 15px;
    color: white;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.action-btn:hover::before {
    left: 100%;
}

.action-btn:hover {
    transform: translateY(-3px);
    background: rgba(255, 255, 255, 0.3);
    color: white;
    text-decoration: none;
    box-shadow: var(--shadow-lg);
}

.action-btn i {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.action-btn.deposit { background: linear-gradient(135deg, #10b981, #059669); }
.action-btn.withdraw { background: linear-gradient(135deg, #ef4444, #dc2626); }
.action-btn.exchange { background: linear-gradient(135deg, #f59e0b, #d97706); }
.action-btn.invest { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.action-btn.rewards { background: linear-gradient(135deg, #ec4899, #db2777); }
.action-btn.giftcard { background: linear-gradient(135deg, #6366f1, #4f46e5); }

/* Analytics Card */
.analytics-card {
    background: white;
    border-radius: var(--radius-2xl);
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
}

.analytics-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
}

.analytics-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.analytics-title i {
    color: var(--primary-color);
}

.chart-container {
    height: 300px;
    position: relative;
}

/* Investment Cards */
.investment-card {
    background: white;
    border-radius: var(--radius-2xl);
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.investment-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.investment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.investment-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.investment-amount {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--success-color);
    margin: 0;
}

.progress-container {
    margin-bottom: 20px;
}

.progress-bar-modern {
    height: 8px;
    background: var(--light-color);
    border-radius: var(--radius-sm);
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border-radius: var(--radius-sm);
    transition: width 0.3s ease;
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.investment-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.investment-detail {
    text-align: center;
}

.investment-detail-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
    font-weight: 600;
}

.investment-detail-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
}

/* History Cards */
.history-card-modern {
    background: white;
    border-radius: var(--radius-xl);
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.history-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.history-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.history-type {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: var(--text-primary);
}

.history-type i {
    color: var(--primary-color);
    font-size: 1.2rem;
}

.history-amount {
    font-size: 1.3rem;
    font-weight: 700;
}

.history-amount.positive {
    color: var(--success-color);
}

.history-amount.negative {
    color: var(--danger-color);
}

.history-card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.history-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.history-date,
.history-ref {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.history-date i,
.history-ref i {
    color: var(--text-muted);
}

.history-card-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Buttons */
.btn-modern {
    padding: 10px 20px;
    border-radius: var(--radius-md);
    border: none;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary-modern {
    background: var(--primary-color);
    color: white;
}

.btn-primary-modern:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-danger-modern {
    background: var(--danger-color);
    color: white;
}

.btn-danger-modern:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Status Badges */
.status-badge {
    padding: 6px 12px;
    border-radius: var(--radius-sm);
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-success {
    background: #dcfce7;
    color: #166534;
}

.status-warning {
    background: #fef3c7;
    color: #92400e;
}

.status-danger {
    background: #fee2e2;
    color: #991b1b;
}

.status-info {
    background: #dbeafe;
    color: #1e40af;
}

.status-active {
    background: #e0e7ff;
    color: #3730a3;
}

.status-default {
    background: #f3f4f6;
    color: #374151;
}

/* Transaction Details Modal */
.transaction-details-modern {
    padding: 20px;
}

.transaction-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--border-color);
}

.transaction-header h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.transaction-id {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-secondary);
    background: var(--light-color);
    padding: 8px 16px;
    border-radius: var(--radius-md);
    display: inline-block;
}

.transaction-summary-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card-modern {
    background: var(--light-color);
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
}

.summary-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.summary-card-modern.total {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
}

.summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.summary-card-modern.total .summary-icon {
    background: rgba(255, 255, 255, 0.2);
}

.summary-content {
    flex: 1;
}

.summary-label {
    display: block;
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
}

.summary-card-modern.total .summary-label,
.summary-card-modern.total .summary-value {
    color: white;
}

.summary-value.fee {
    color: var(--danger-color);
}

.transaction-info-modern {
    background: var(--light-color);
    border-radius: var(--radius-lg);
    padding: 25px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
    border-bottom: none;
}

.info-item.full-width {
    flex-direction: column;
    align-items: flex-start;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-label {
    display: block;
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.payment-details-modern {
    margin-top: 10px;
}

.detail-pair-modern {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
}

.detail-pair-modern:last-child {
    border-bottom: none;
}

.detail-key-modern {
    font-weight: 600;
    color: var(--text-secondary);
}

.detail-value-modern {
    color: var(--text-primary);
    font-weight: 500;
}

/* Load More Button */
.load-more-container {
    text-align: center;
    margin-top: 30px;
}

.load-more-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: var(--radius-lg);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.load-more-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: var(--text-primary);
}

.empty-state p {
    font-size: 1rem;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 15px;
    }
    
    .balance-amount {
        font-size: 2.5rem;
    }
    
    .balance-amount-container {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .balance-stats {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .analytics-card {
        padding: 20px;
    }
    
    .chart-container {
        height: 250px;
    }
    
    .investment-details {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .history-card-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .history-card-body {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .history-card-actions {
        justify-content: stretch;
    }
    
    .history-card-actions .btn-modern {
        flex: 1;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.5rem;
    }
    
    .balance-amount {
        font-size: 2rem;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .investment-details {
        grid-template-columns: 1fr;
    }
    
    .transaction-summary-modern {
        grid-template-columns: 1fr;
    }
}

/* Dark Mode Toggle */
.theme-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--primary-color);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2rem;
    box-shadow: var(--shadow-lg);
    transition: all 0.3s ease;
    z-index: 1000;
}

.theme-toggle:hover {
    transform: scale(1.1);
    box-shadow: var(--shadow-xl);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

/* Print Styles */
@media print {
    body {
        background: white !important;
    }
    
    .balance-card {
        background: white !important;
        color: black !important;
        box-shadow: none !important;
    }
    
    .action-grid {
        display: none !important;
    }
    
    .theme-toggle {
        display: none !important;
    }
}
</style>

<div class="dashboard-container">
    <!-- Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
        <i class="fa fa-moon-o"></i>
    </button>

    <!-- Page Header -->
    <div class="page-header fade-in-up">
        <h1 class="page-title">
            <i class="fa fa-wallet"></i>
            <?php echo $PAGE_TITLE; ?>
        </h1>
    </div>

    <!-- Balance Card -->
    <div class="balance-card fade-in-up">
        <div class="balance-content">
            <div class="balance-label">Total Available Balance</div>
            <div class="balance-amount-container">
                <?php
                    $user_balance_formatted = GetUserBalance($userid);
                    $user_balance_raw = GetUserBalance($userid, 1);
                ?>
                <h2 class="balance-amount" id="balance-amount" data-balance="<?php echo htmlspecialchars($user_balance_formatted); ?>" data-balance-raw="<?php echo htmlspecialchars($user_balance_raw); ?>">
                    <?php echo $user_balance_formatted; ?>
                </h2>
                <button class="balance-toggle" id="toggle-balance-visibility" title="Show/Hide Balance">
                    <i class="fa fa-eye-slash"></i>
                </button>
            </div>
            
            <div class="balance-stats">
                <a href="<?php echo SITE_URL; ?>history.php" class="stat-card">
                    <span class="stat-value">
                        <?php
                            $conf_ex_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM exchangerix_exchanges WHERE user_id=? AND (status='confirmed' OR status='paid')");
                            mysqli_stmt_bind_param($conf_ex_stmt, "i", $userid);
                            mysqli_stmt_execute($conf_ex_stmt);
                            $conf_ex_result = mysqli_stmt_get_result($conf_ex_stmt);
                            echo number_format(mysqli_fetch_assoc($conf_ex_result)['total'] ?? 0);
                            mysqli_stmt_close($conf_ex_stmt);
                        ?>
                    </span>
                    <span class="stat-label">My Exchanges</span>
                </a>
                
                <a href="<?php echo SITE_URL; ?>invite.php" class="stat-card">
                    <span class="stat-value">
                        <?php
                            $ref_earnings_stmt = mysqli_prepare($conn, "SELECT SUM(amount) AS total_earnings FROM exchangerix_transactions WHERE user_id=? AND payment_type='Affiliate Commission' AND status='confirmed'");
                            mysqli_stmt_bind_param($ref_earnings_stmt, "i", $userid);
                            mysqli_stmt_execute($ref_earnings_stmt);
                            $ref_earnings_result = mysqli_stmt_get_result($ref_earnings_stmt);
                            $total_referral_earnings = mysqli_fetch_assoc($ref_earnings_result)['total_earnings'] ?? 0;
                            echo DisplayMoney($total_referral_earnings);
                            mysqli_stmt_close($ref_earnings_stmt);
                        ?>
                    </span>
                    <span class="stat-label">My Earnings</span>
                </a>
                
                <a href="<?php echo SITE_URL; ?>myreviews.php" class="stat-card">
                    <span class="stat-value">
                        <?php
                            $review_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM exchangerix_reviews WHERE user_id=? AND status='active'");
                            mysqli_stmt_bind_param($review_stmt, "i", $userid);
                            mysqli_stmt_execute($review_stmt);
                            $review_result = mysqli_stmt_get_result($review_stmt);
                            echo number_format(mysqli_fetch_assoc($review_result)['total'] ?? 0);
                            mysqli_stmt_close($review_stmt);
                        ?>
                    </span>
                    <span class="stat-label">My Reviews</span>
                </a>
            </div>

            <div class="action-grid">
                <a href="<?php echo SITE_URL; ?>invite.php" class="action-btn exchange">
                    <i class="fa fa-money"></i>
                    <span>Make Money</span>
                </a>
                <a href="<?php echo SITE_URL; ?>deposit.php" class="action-btn deposit">
                    <i class="fa fa-download"></i>
                    <span>Deposit</span>
                </a>
                <a href="<?php echo SITE_URL; ?>invest.php" class="action-btn invest">
                    <i class="fa fa-chart-line"></i>
                    <span>Grow & Earn</span>
                </a>
                <a href="https://chinaitechpay.com/social-boosting.php" class="action-btn rewards">
                    <i class="fa fa-share-alt"></i>
                    <span>Social Boosting</span>
                </a>
                <a href="<?php echo SITE_URL; ?>shop.php" class="action-btn giftcard">
                    <i class="fa fa-shopping-bag"></i>
                    <span>Shop Cards</span>
                </a>
                <a href="<?php echo SITE_URL; ?>withdraw.php" class="action-btn withdraw">
                    <i class="fa fa-credit-card"></i>
                    <span>Withdraw</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Analytics Card -->
    <div class="analytics-card fade-in-up">
        <div class="analytics-header">
            <h2 class="analytics-title">
                <i class="fa fa-line-chart"></i>
                Analytics Overview
            </h2>
        </div>
        <div class="chart-container">
            <canvas id="analyticsChart"></canvas>
        </div>
    </div>

    <!-- Investments Section -->
    <?php
    $investments_query = "
        SELECT *, 
               DATEDIFF(maturity_date, start_date) AS total_duration_days,
               DATEDIFF(NOW(), start_date) AS elapsed_days
        FROM exchangerix_investments 
        WHERE user_id = ? 
        AND status IN ('active', 'pending')
        ORDER BY created_at DESC
    ";
    $stmt_investments = $conn->prepare($investments_query);
    $stmt_investments->bind_param("i", $userid);
    $stmt_investments->execute();
    $investments_result = $stmt_investments->get_result();
    $total_investments = $investments_result->num_rows;
    ?>

    <?php if ($total_investments > 0) : ?>
    <div class="analytics-card fade-in-up">
        <div class="analytics-header">
            <h2 class="analytics-title">
                <i class="fa fa-bank"></i>
                My Investments
            </h2>
            <span class="status-badge status-info"><?php echo $total_investments; ?> Active</span>
        </div>
        
        <?php while ($row = $investments_result->fetch_assoc()) : ?>
        <div class="investment-card">
            <div class="investment-header">
                <h3 class="investment-title"><?php echo htmlspecialchars($row['plan_name']); ?></h3>
                <div class="investment-amount"><?php echo DisplayMoney($row['return_amount']); ?></div>
            </div>
            
            <div class="progress-container">
                <?php
                    $progress_percent = 0;
                    if ($row['status'] == 'active' && $row['total_duration_days'] > 0 && $row['elapsed_days'] > 0) {
                        $progress_percent = ($row['elapsed_days'] / $row['total_duration_days']) * 100;
                        if ($progress_percent > 100) $progress_percent = 100;
                    }
                ?>
                <div class="progress-bar-modern">
                    <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 5px; font-size: 0.9rem; color: var(--text-secondary);">
                    <span><?php echo $row['elapsed_days']; ?> days elapsed</span>
                    <span><?php echo round($progress_percent, 1); ?>% complete</span>
                </div>
            </div>
            
            <div class="investment-details">
                <div class="investment-detail">
                    <div class="investment-detail-label">Invested Amount</div>
                    <div class="investment-detail-value"><?php echo DisplayMoney($row['amount']); ?></div>
                </div>
                <div class="investment-detail">
                    <div class="investment-detail-label">Daily Interest</div>
                    <div class="investment-detail-value" style="color: var(--success-color);">+<?php echo DisplayMoney($row['daily_interest_amount']); ?></div>
                </div>
                <div class="investment-detail">
                    <div class="investment-detail-label">Total Paid</div>
                    <div class="investment-detail-value"><?php echo DisplayMoney($row['total_interest_paid']); ?></div>
                </div>
                <div class="investment-detail">
                    <div class="investment-detail-label">Maturity Date</div>
                    <div class="investment-detail-value"><?php echo date('d M Y', strtotime($row['maturity_date'])); ?></div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Payment History Section -->
    <?php
    $results_per_page_trans = 5;
    
    $total_trans_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM exchangerix_transactions WHERE user_id=? AND status!='unknown'");
    mysqli_stmt_bind_param($total_trans_stmt, "i", $userid);
    mysqli_stmt_execute($total_trans_stmt);
    $total_trans_result = mysqli_stmt_get_result($total_trans_stmt);
    $total_trans = mysqli_fetch_assoc($total_trans_result)['total'] ?? 0;
    mysqli_stmt_close($total_trans_stmt);
    
    $date_format_sql_list = defined('DATE_FORMAT') ? DATE_FORMAT : '%e %b %Y';
    $sql_date_display_format = str_replace("<sup>%h:%i %p</sup>", "", $date_format_sql_list);
    $query_trans_list = "SELECT *, DATE_FORMAT(created, ?) AS date_created_list FROM exchangerix_transactions WHERE user_id=? AND status!='unknown' ORDER BY created DESC LIMIT 0, ?";
    $stmt_trans_list = mysqli_prepare($conn, $query_trans_list);
    mysqli_stmt_bind_param($stmt_trans_list, "sii", $sql_date_display_format, $userid, $results_per_page_trans);
    mysqli_stmt_execute($stmt_trans_list);
    $result_trans_list = mysqli_stmt_get_result($stmt_trans_list);
    ?>

    <div class="analytics-card fade-in-up">
        <div class="analytics-header">
            <h2 class="analytics-title">
                <i class="fa fa-list-alt"></i>
                Payment History
            </h2>
            <span class="status-badge status-info"><?php echo $total_trans; ?> Total</span>
        </div>
        
        <?php if ($total_trans > 0) : ?>
            <div id="payment-history-list">
                <?php while ($row = mysqli_fetch_array($result_trans_list)) : ?>
                    <div class="history-card-modern">
                        <div class="history-card-header">
                            <div class="history-type">
                                <i class="fa fa-<?php echo $row['payment_type'] == 'Deposit' ? 'download' : ($row['payment_type'] == 'Withdrawal' ? 'upload' : 'exchange-alt'); ?>"></i>
                                <span><?php echo htmlspecialchars($row['payment_type']); ?></span>
                            </div>
                            <div class="history-amount <?php echo $row['payment_type'] == 'Deposit' ? 'positive' : 'negative'; ?>">
                                <?php echo $row['payment_type'] == 'Deposit' ? '+' : '-'; ?><?php echo DisplayMoney($row['amount']); ?>
                            </div>
                        </div>
                        <div class="history-card-body">
                            <div class="history-info">
                                <div class="history-date">
                                    <i class="fa fa-calendar"></i>
                                    <?php echo $row['date_created_list']; ?>
                                </div>
                                <div class="history-ref">
                                    <i class="fa fa-hashtag"></i>
                                    <?php echo htmlspecialchars($row['reference_id']); ?>
                                </div>
                            </div>
                            <div class="history-status">
                                <?php echo getTransactionStatusLabel($row['status']); ?>
                            </div>
                        </div>
                        <div class="history-card-actions">
                            <button type="button" class="btn-modern btn-primary-modern view-details-btn-modern" data-id="<?php echo $row['transaction_id']; ?>">
                                <i class="fa fa-eye"></i> View Details
                            </button>
                            <?php if (defined('CANCEL_WITHDRAWAL') && CANCEL_WITHDRAWAL == 1 && $row['payment_type'] == "Withdrawal" && $row['status'] == "request") { ?>
                                <button type="button" class="btn-modern btn-danger-modern cancel-btn-modern" data-id="<?php echo $row['transaction_id']; ?>">
                                    <i class="fa fa-times"></i> Cancel
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <?php if ($total_trans > $results_per_page_trans) : ?>
                <div class="load-more-container">
                    <button id="loadMoreHistoryBtn" class="load-more-btn" data-page="2">
                        <i class="fa fa-plus"></i> Load More
                    </button>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="empty-state">
                <i class="fa fa-list-alt"></i>
                <h3>No Payment History</h3>
                <p>Your transaction history will appear here once you start using our services.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="transactionDetailsModalBody">
                <div class="text-center p-5">
                    <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-primary-modern" onclick="downloadReceipt()">
                    <i class="fa fa-download"></i> Download Receipt
                </button>
                <button type="button" class="btn-modern btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once("inc/footer.inc.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
jQuery(document).ready(function($) {
    // Theme Toggle Functionality
    function toggleTheme() {
        const body = document.body;
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        body.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        const themeIcon = document.querySelector('.theme-toggle i');
        themeIcon.className = newTheme === 'dark' ? 'fa fa-sun-o' : 'fa fa-moon-o';
    }

    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-theme', savedTheme);
    const themeIcon = document.querySelector('.theme-toggle i');
    themeIcon.className = savedTheme === 'dark' ? 'fa fa-sun-o' : 'fa fa-moon-o';

    // Balance Visibility Toggle
    let isBalanceVisible = true;
    const balanceEl = $('#balance-amount');
    const toggleBtn = $('#toggle-balance-visibility');
    const toggleIcon = toggleBtn.find('i');
    const originalBalance = balanceEl.data('balance');
    const maskedBalance = '••••••';

    toggleBtn.on('click', function() {
        isBalanceVisible = !isBalanceVisible;
        if (isBalanceVisible) {
            balanceEl.text(originalBalance);
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            toggleBtn.attr('title', 'Hide Balance');
        } else {
            balanceEl.text(maskedBalance);
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            toggleBtn.attr('title', 'Show Balance');
        }
    });

    // Analytics Chart
    const analyticsData = <?php echo json_encode($final_analytics_data); ?>;
    const ctx = document.getElementById('analyticsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: analyticsData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { 
                    mode: 'index', 
                    intersect: false 
                },
                scales: {
                    x: { 
                        stacked: true, 
                        grid: { display: false },
                        ticks: {
                            color: 'var(--text-secondary)'
                        }
                    },
                    y: { 
                        stacked: true, 
                        grid: { 
                            color: 'var(--border-color)', 
                            borderDash: [2, 4] 
                        },
                        ticks: {
                            color: 'var(--text-secondary)'
                        }
                    }
                },
                plugins: {
                    legend: { 
                        position: 'top', 
                        align: 'start', 
                        labels: { 
                            boxWidth: 12, 
                            padding: 20,
                            color: 'var(--text-primary)'
                        } 
                    },
                    tooltip: { 
                        backgroundColor: 'var(--dark-color)', 
                        titleFont: { weight: 'bold' }, 
                        bodySpacing: 4, 
                        padding: 10, 
                        cornerRadius: 4, 
                        displayColors: true 
                    }
                }
            }
        });
    }

    // Load More History
    $('#loadMoreHistoryBtn').on('click', function() {
        const button = $(this);
        const page = button.data('page');
        const originalText = button.html();
        
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
        
        $.ajax({
            url: 'mybalance.php',
            type: 'GET',
            data: { action: 'load_more_history', page: page },
            dataType: 'json',
            success: function(response) {
                if (response.html && response.html.trim().length > 0) {
                    $('#payment-history-list').append(response.html);
                    button.data('page', page + 1);
                }
                if (!response.has_more) { 
                    button.parent().hide(); 
                }
            },
            error: function() {
                button.parent().before('<div class="alert alert-danger text-center">Failed to load more history. Please refresh the page.</div>');
                button.hide();
            },
            complete: function() { 
                button.prop('disabled', false).html(originalText); 
            }
        });
    });

    // Transaction Details Modal
    let lastScrollPosition = 0;
    const transactionModal = $('#transactionDetailsModal');
    const transactionModalBody = $('#transactionDetailsModalBody');
    
    $(document).on('click', '.view-details-btn-modern', function(e) {
        e.preventDefault();
        lastScrollPosition = $(window).scrollTop();
        const transactionId = $(this).data('id');
        
        transactionModal.modal('show');
        transactionModalBody.html('<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading...</p></div>');
        
        $.ajax({
            url: 'mybalance.php',
            type: 'GET',
            data: { action: 'get_transaction_details', id: transactionId },
            success: function(response) {
                transactionModalBody.html('<div id="printable-content">' + response + '</div>');
            },
            error: function() { 
                transactionModalBody.html('<div id="printable-content"><div class="alert alert-danger m-3 p-3 text-center">Failed to load details.</div></div>'); 
            }
        });
    });

    // Cancel Transaction
    $(document).on('click', '.cancel-btn-modern', function(e) {
        e.preventDefault();
        const transactionId = $(this).data('id');
        
        if (confirm('Are you sure you want to cancel this withdrawal request?')) {
            window.location.href = 'mybalance.php?id=' + transactionId + '&act=cancel';
        }
    });

    // Download Receipt Function
    window.downloadReceipt = async function() {
        const printableContent = document.getElementById('printable-content');
        if (!printableContent) {
            alert('Unable to generate receipt');
            return;
        }

        const downloadBtn = document.querySelector('.modal-footer .btn-modern');
        if (!downloadBtn) return;

        const originalText = downloadBtn.innerHTML;
        downloadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';
        downloadBtn.disabled = true;

        try {
            const tempContainer = document.createElement('div');
            tempContainer.style.cssText = `
                position: fixed;
                left: -9999px;
                top: 0;
                width: 800px;
                background: white;
                padding: 30px;
                border: 2px solid #6366f1;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                font-family: Arial, sans-serif;
                color: #333;
                line-height: 1.5;
            `;

            // Extract transaction details
            const allText = printableContent.textContent || '';
            const transactionId = allText.match(/(CHINA-[A-Z0-9]+)/i)?.[0] || 'Receipt';
            const status = document.querySelector('.status-badge')?.textContent?.trim() || 'Unknown';

            tempContainer.innerHTML = `
                <div style="text-align: center; border-bottom: 3px solid #6366f1; padding-bottom: 20px; margin-bottom: 25px;">
                    <h2 style="color: #333; margin: 10px 0 15px 0; font-size: 24px; font-weight: bold;">OFFICIAL TRANSACTION RECEIPT</h2>
                    <div style="display: flex; justify-content: center; gap: 30px; font-size: 18px; align-items: center; margin: 15px 0;">
                        <span style="color: #666;">Transaction ID: <strong>${transactionId}</strong></span>
                        <span style="color: #666;">Status: <strong>${status}</strong></span>
                    </div>
                    <p style="color: #888; margin: 15px 0 0 0; font-size: 14px;">Generated on: ${new Date().toLocaleString()}</p>
                </div>
                
                <div style="margin: 25px 0;">
                    ${printableContent.innerHTML}
                </div>
                
                <div style="text-align: center; margin-top: 25px; padding: 15px; background: #f8fafc; border-radius: 8px;">
                    <p style="margin: 5px 0; font-size: 15px; color: #666; font-weight: bold;">
                        🔐 DOCUMENT AUTHENTICITY CONFIRMED
                    </p>
                    <p style="margin: 5px 0; font-size: 14px; color: #888;">
                        Verify this receipt online at: <strong>https://chinaitechpay.com/verify.php</strong>
                    </p>
                </div>
            `;

            document.body.appendChild(tempContainer);

            const canvas = await html2canvas(tempContainer, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
                width: tempContainer.offsetWidth,
                height: tempContainer.scrollHeight
            });

            document.body.removeChild(tempContainer);

            const imageData = canvas.toDataURL('image/png', 1.0);
            const link = document.createElement('a');
            link.href = imageData;
            link.download = `ChinaitechPay_Receipt_${transactionId}.png`;
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

        } catch (error) {
            console.error('Error generating receipt:', error);
            alert('Error generating receipt. Please try again.');
        }

        setTimeout(() => {
            downloadBtn.innerHTML = originalText;
            downloadBtn.disabled = false;
        }, 1000);
    };

    // Modal cleanup
    transactionModal.on('hidden.bs.modal', function () {
        transactionModalBody.html('<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading...</p></div>');
        $(window).scrollTop(lastScrollPosition);
    });

    // Add fade-in animation to cards
    $('.fade-in-up').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
    });
});

// Make theme toggle globally available
window.toggleTheme = function() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    const themeIcon = document.querySelector('.theme-toggle i');
    themeIcon.className = newTheme === 'dark' ? 'fa fa-sun-o' : 'fa fa-moon-o';
};
</script>