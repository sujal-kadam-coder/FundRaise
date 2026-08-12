<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

$campaignId = 0;
$amount = 0;
$message = '';
$isAnon = 0;

// ---- Step 2: "I've completed the payment" — finalize the donation ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm') {
    if (!is_logged_in()) { header('Location: login.php'); exit; }

    $pending = $_SESSION['pending_donation'] ?? null;
    if (!$pending) { header('Location: campaigns.php'); exit; }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('INSERT INTO donations (campaign_id, user_id, amount, message, is_anonymous) VALUES (?, ?, ?, ?, ?)');
        $uid = current_user_id();
        $stmt->bind_param('iidsi', $pending['campaign_id'], $uid, $pending['amount'], $pending['message'], $pending['is_anonymous']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE campaigns SET raised_amount = raised_amount + ? WHERE id = ?');
        $stmt->bind_param('di', $pending['amount'], $pending['campaign_id']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $campaignId = $pending['campaign_id'];
        unset($_SESSION['pending_donation']);
        header('Location: campaign.php?id=' . $campaignId . '&donated=1');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: campaign.php?id=' . $pending['campaign_id'] . '&payment_failed=1');
        exit;
    }
}

// ---- Step 1: coming from the donate form on campaign.php ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_id'])) {
    $campaignId = (int)$_POST['campaign_id'];
    $amount = (float)($_POST['amount'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $isAnon = isset($_POST['is_anonymous']) ? 1 : 0;

    if (!is_logged_in()) {
        header('Location: login.php?next=' . urlencode('campaign.php?id=' . $campaignId));
        exit;
    }
    if ($amount < 10 || $amount > 1000000) {
        header('Location: campaign.php?id=' . $campaignId . '&invalid_amount=1');
        exit;
    }

    $_SESSION['pending_donation'] = [
        'campaign_id' => $campaignId,
        'amount' => $amount,
        'message' => $message,
        'is_anonymous' => $isAnon,
    ];
} elseif (!empty($_SESSION['pending_donation'])) {
    // Refreshing this page — reuse what's already pending instead of losing it
    $p = $_SESSION['pending_donation'];
    $campaignId = $p['campaign_id'];
    $amount = $p['amount'];
    $message = $p['message'];
    $isAnon = $p['is_anonymous'];
} else {
    header('Location: campaigns.php');
    exit;
}

$stmt = $conn->prepare('SELECT id, title, cover_color FROM campaigns WHERE id = ?');
$stmt->bind_param('i', $campaignId);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$campaign) { header('Location: campaigns.php'); exit; }

// A fake-but-realistic looking reference number for the checkout screen
$txnRef = 'FR' . date('ymd') . strtoupper(substr(md5($campaignId . $amount . time()), 0, 6));

$pageTitle = 'Complete Your Payment';
$basePath = '';
include 'includes/header.php';
?>

<section style="padding: 50px 24px 80px;">
  <div class="pay-page-card">
    <div class="pay-page-head">
      <span class="pay-brand-badge">🔒</span>
      <div>
        <div class="pay-brand-name">FundRaise Pay</div>
        <div class="pay-brand-sub">Secure UPI Checkout</div>
      </div>
    </div>

    <div class="pay-summary">
      <div class="pay-summary-row">
        <span>Paying for</span>
        <b><?php echo h($campaign['title']); ?></b>
      </div>
      <div class="pay-summary-row">
        <span>Reference ID</span>
        <b style="font-family:'JetBrains Mono', monospace; font-size:0.82rem;"><?php echo h($txnRef); ?></b>
      </div>
    </div>

    <div class="pay-amount-display">₹<?php echo number_format($amount, 0); ?></div>

    <div class="pay-qr-wrap">
      <img src="images/payment-qr.png" alt="Scan to pay QR code" class="pay-qr-img">
    </div>
    <p class="pay-sub" style="text-align:center;">Scan with any UPI app to pay <b>₹<?php echo number_format($amount, 0); ?></b></p>

    <div class="pay-apps">
      <span>Works with</span>
      <div class="pay-app-chips">
        <span class="pay-app-chip">GPay</span>
        <span class="pay-app-chip">PhonePe</span>
        <span class="pay-app-chip">Paytm</span>
        <span class="pay-app-chip">BHIM</span>
      </div>
    </div>

    <form method="POST" action="payment.php">
      <input type="hidden" name="action" value="confirm">
      <button type="submit" class="btn btn-primary btn-block" id="confirmPayBtn">I've Completed the Payment</button>
    </form>
    <a href="campaign.php?id=<?php echo $campaignId; ?>" class="pay-cancel-link">Cancel and go back</a>
    <p class="pay-note">This is a demo checkout for a student project — no real transaction is processed by this site itself. Clicking confirm records the donation in the database once you've completed payment via the QR code.</p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script>
document.getElementById('confirmPayBtn').addEventListener('click', function (e) {
  e.preventDefault();
  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="pay-btn-spinner"></span> Confirming payment…';
  setTimeout(function () { btn.closest('form').submit(); }, 1300);
});
</script>
