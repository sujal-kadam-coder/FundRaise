<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT c.*, cat.name AS category_name, u.full_name AS creator_name
     FROM campaigns c
     JOIN categories cat ON c.category_id = cat.id
     JOIN users u ON c.user_id = u.id
     WHERE c.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$campaign) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    $basePath = '';
    include 'includes/header.php';
    echo '<section style="text-align:center;"><h2>Campaign not found</h2><p style="margin-top:10px;"><a href="campaigns.php" style="color:var(--teal); font-weight:600;">← Back to all campaigns</a></p></section>';
    include 'includes/footer.php';
    exit;
}

$isOwner = is_logged_in() && $campaign['user_id'] == current_user_id();

if (!$isOwner && $campaign['status'] !== 'Approved') {
    http_response_code(403);
    $pageTitle = 'Not Available';
    $basePath = '';
    include 'includes/header.php';
    echo '<section style="text-align:center;"><h2>This campaign isn\'t public yet</h2><p style="margin-top:10px;"><a href="campaigns.php" style="color:var(--teal); font-weight:600;">← Back to all campaigns</a></p></section>';
    include 'includes/footer.php';
    exit;
}

// ---- Show a thank-you banner if redirected back after a successful payment ----
$donateSuccess = isset($_GET['donated']);
$justSubmitted = isset($_GET['submitted']) && $isOwner;
$donateErrors = [];

// ---- Handle posting a new comment/question ----
$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'comment') {
    if (!is_logged_in()) {
        header('Location: login.php?next=' . urlencode('campaign.php?id=' . $id));
        exit;
    }
    $commentText = trim($_POST['comment'] ?? '');
    if ($commentText === '' || strlen($commentText) < 3) {
        $commentError = 'Please write a short message before posting.';
    } elseif (strlen($commentText) > 500) {
        $commentError = 'Keep your comment under 500 characters.';
    } else {
        $stmt = $conn->prepare('INSERT INTO campaign_comments (campaign_id, user_id, comment) VALUES (?, ?, ?)');
        $uid = current_user_id();
        $stmt->bind_param('iis', $id, $uid, $commentText);
        $stmt->execute();
        $stmt->close();
        header('Location: campaign.php?id=' . $id . '#panel-comments-anchor');
        exit;
    }
}

// ---- Updates ----
$updates = [];
$stmt = $conn->prepare('SELECT title, content, created_at FROM campaign_updates WHERE campaign_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $updates[] = $row; }
$stmt->close();

// ---- Donors ----
$donors = [];
$stmt = $conn->prepare(
    "SELECT d.amount, d.message, d.is_anonymous, d.created_at, u.full_name
     FROM donations d JOIN users u ON d.user_id = u.id
     WHERE d.campaign_id = ? ORDER BY d.created_at DESC"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $donors[] = $row; }
$stmt->close();

// ---- Comments / Q&A ----
$comments = [];
$stmt = $conn->prepare(
    "SELECT cm.comment, cm.created_at, u.full_name
     FROM campaign_comments cm JOIN users u ON cm.user_id = u.id
     WHERE cm.campaign_id = ? ORDER BY cm.created_at DESC"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $comments[] = $row; }
$stmt->close();

$pct = progress_percent($campaign['raised_amount'], $campaign['goal_amount']);
$left = days_left($campaign['deadline']);

$pageTitle = $campaign['title'];
$basePath = '';
include 'includes/header.php';
?>

<section class="campaign-hero" style="padding-top:34px;">
  <div class="container">
    <?php if ($isOwner && $campaign['status'] !== 'Approved'): ?>
      <span class="badge badge-<?php echo strtolower($campaign['status']); ?>" style="margin-bottom:14px; display:inline-block;">Status: <?php echo h($campaign['status']); ?> (only visible to you)</span><br>
    <?php endif; ?>
    <span class="cat-tag"><?php echo h($campaign['category_name']); ?></span>
    <h1><?php echo h($campaign['title']); ?></h1>
    <p class="by">by <?php echo h($campaign['creator_name']); ?> · <?php echo h($campaign['short_description']); ?></p>
    <?php if ($isOwner): ?>
      <div class="owner-actions">
        <a href="edit-campaign.php?id=<?php echo $id; ?>" class="btn btn-sm" style="background:rgba(255,255,255,0.12); color:#fff;">✏️ Edit</a>
        <a href="manage-campaign.php?id=<?php echo $id; ?>" class="btn btn-sm" style="background:rgba(255,255,255,0.12); color:#fff;">📋 Manage</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<section>
  <div class="detail-grid">
    <div>
      <?php echo cover_html($campaign, 'detail-cover'); ?>

      <div class="detail-tabs">
        <div class="detail-tab active" data-panel="panel-story">Story</div>
        <div class="detail-tab" data-panel="panel-updates">Updates (<?php echo count($updates); ?>)</div>
        <div class="detail-tab" data-panel="panel-donors">Donors (<?php echo count($donors); ?>)</div>
        <div class="detail-tab" data-panel="panel-comments">Q&amp;A (<?php echo count($comments); ?>)</div>
      </div>

      <div class="detail-panel active" id="panel-story">
        <p class="story-text"><?php echo nl2br(h($campaign['story'])); ?></p>
      </div>

      <div class="detail-panel" id="panel-updates">
        <?php if (empty($updates)): ?>
          <p style="color:var(--slate);">No updates posted yet.</p>
        <?php endif; ?>
        <?php foreach ($updates as $u): ?>
          <div class="update-item">
            <div class="date"><?php echo date('d M Y', strtotime($u['created_at'])); ?></div>
            <h4><?php echo h($u['title']); ?></h4>
            <p><?php echo nl2br(h($u['content'])); ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="detail-panel" id="panel-donors">
        <?php if (empty($donors)): ?>
          <p style="color:var(--slate);">No donations yet — be the first to support this campaign!</p>
        <?php endif; ?>
        <?php foreach ($donors as $d): ?>
          <div class="donor-item">
            <div>
              <div class="name"><?php echo $d['is_anonymous'] ? 'Anonymous Donor' : h($d['full_name']); ?></div>
              <?php if (!empty($d['message'])): ?><div class="msg">"<?php echo h($d['message']); ?>"</div><?php endif; ?>
            </div>
            <div class="amt"><?php echo money($d['amount']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="detail-panel" id="panel-comments">
        <a id="panel-comments-anchor"></a>
        <?php if (is_logged_in()): ?>
          <form method="POST" action="campaign.php?id=<?php echo $id; ?>" class="comment-form">
            <input type="hidden" name="form_type" value="comment">
            <textarea name="comment" rows="3" placeholder="Ask the creator a question or leave a note of support…"></textarea>
            <?php if ($commentError): ?><div class="error-msg" style="margin-bottom:8px;"><?php echo h($commentError); ?></div><?php endif; ?>
            <button type="submit" class="btn btn-teal btn-sm">Post</button>
          </form>
        <?php else: ?>
          <p class="form-note"><a href="login.php?next=<?php echo urlencode('campaign.php?id=' . $id); ?>" style="color:var(--teal); font-weight:600;">Log in</a> to ask a question or leave a comment.</p>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
          <p style="color:var(--slate); margin-top:10px;">No questions yet — be the first to ask something.</p>
        <?php endif; ?>
        <?php foreach ($comments as $c): ?>
          <div class="comment-item">
            <div class="comment-head">
              <span class="comment-name"><?php echo h($c['full_name']); ?></span>
              <span class="comment-date"><?php echo date('d M Y', strtotime($c['created_at'])); ?></span>
            </div>
            <p><?php echo nl2br(h($c['comment'])); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <div class="donate-panel">
        <div class="raised"><?php echo money($campaign['raised_amount']); ?></div>
        <div class="goal">raised of <?php echo money($campaign['goal_amount']); ?> goal</div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $pct; ?>%;"></div></div>

        <div class="meta-row">
          <div><b><?php echo $pct; ?>%</b>funded</div>
          <div><b><?php echo count($donors); ?></b>donors</div>
          <div><b><?php echo $left; ?></b>days left</div>
        </div>

        <?php if ($justSubmitted): ?>
          <div class="alert alert-success">🎉 Your campaign has been submitted for review. It'll go live publicly once our team approves it — usually within 24 hours.</div>
        <?php endif; ?>
        <?php if ($donateSuccess): ?>
          <div class="alert alert-success">🎉 Payment received — thank you for your donation!</div>
        <?php endif; ?>
        <?php if (isset($_GET['invalid_amount'])): ?>
          <div class="alert alert-error">Please enter a valid donation amount (₹10 – ₹10,00,000).</div>
        <?php endif; ?>
        <?php if (isset($_GET['payment_failed'])): ?>
          <div class="alert alert-error">Something went wrong confirming your payment. Please try again.</div>
        <?php endif; ?>

        <?php if ($campaign['status'] === 'Approved' && $left > 0): ?>
          <form method="POST" action="payment.php" id="donateForm" novalidate>
            <input type="hidden" name="campaign_id" value="<?php echo $id; ?>">
            <div class="amount-options">
              <div class="amount-chip" data-amount="500">₹500</div>
              <div class="amount-chip" data-amount="1000">₹1,000</div>
              <div class="amount-chip" data-amount="2500">₹2,500</div>
            </div>
            <div class="field" data-field="amount" style="margin-bottom:12px;">
              <label for="amount">Custom Amount (₹)</label>
              <input type="number" id="amount" name="amount" min="10" step="1" placeholder="Enter amount">
              <span class="error-msg"></span>
            </div>
            <div class="field" style="margin-bottom:12px;">
              <label for="message">Message (optional)</label>
              <input type="text" id="message" name="message" placeholder="Leave a message of support">
            </div>
            <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:var(--slate); margin-bottom:16px;">
              <input type="checkbox" name="is_anonymous"> Donate anonymously
            </label>
            <?php if (!is_logged_in()): ?>
              <p class="form-note">You'll need to <a href="login.php?next=campaign.php?id=<?php echo $id; ?>" style="color:var(--teal); font-weight:600;">log in</a> to complete your donation.</p>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-block">Donate Now</button>
            <p style="text-align:center; font-size:0.72rem; color:var(--slate); margin-top:10px; display:flex; align-items:center; justify-content:center; gap:6px;">🔒 Secure UPI payment · Powered by FundRaise Pay</p>
          </form>
        <?php elseif ($left <= 0): ?>
          <p style="color:var(--slate); font-size:0.88rem;">This campaign's deadline has passed and it's no longer accepting donations.</p>
        <?php else: ?>
          <p style="color:var(--slate); font-size:0.88rem;">This campaign is not yet approved for public donations.</p>
        <?php endif; ?>

        <div class="share-block">
          <span class="share-label">Share this campaign</span>
          <div class="share-buttons">
            <?php
              $shareUrl = urlencode(current_url());
              $shareText = urlencode('Support this campaign: ' . $campaign['title']);
            ?>
            <a href="https://wa.me/?text=<?php echo $shareText . '%20' . $shareUrl; ?>" target="_blank" rel="noopener" class="share-btn share-whatsapp" aria-label="Share on WhatsApp">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.8 14.03c-.24.68-1.4 1.3-1.93 1.35-.5.05-1.03.24-3.45-.72-2.9-1.16-4.76-4.14-4.9-4.33-.14-.19-1.17-1.56-1.17-2.98 0-1.42.75-2.11 1.01-2.4.27-.28.58-.36.78-.36l.56.01c.18.01.42-.07.66.5.24.58.83 2 .9 2.14.07.14.12.31.02.5-.1.19-.15.31-.3.48-.14.17-.31.38-.44.51-.14.14-.29.29-.13.57.17.28.75 1.24 1.61 2 1.1.98 2.03 1.29 2.31 1.43.28.14.44.12.6-.07.17-.19.72-.84.91-1.12.19-.29.38-.24.64-.14.26.1 1.66.78 1.94.93.28.14.47.21.53.33.07.12.07.71-.17 1.39z"/></svg>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>" target="_blank" rel="noopener" class="share-btn share-facebook" aria-label="Share on Facebook">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.16 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22c4.78-.78 8.44-4.94 8.44-9.94z"/></svg>
            </a>
            <a href="https://twitter.com/intent/tweet?text=<?php echo $shareText; ?>&url=<?php echo $shareUrl; ?>" target="_blank" rel="noopener" class="share-btn share-twitter" aria-label="Share on X/Twitter">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.24 2H21l-6.55 7.49L22.2 22h-6.19l-4.84-6.34L5.6 22H2.8l7.02-8.02L2 2h6.34l4.38 5.79L18.24 2zm-1.08 18h1.72L7.9 3.9H6.06L17.16 20z"/></svg>
            </a>
            <button type="button" class="share-btn share-copy" id="copyLinkBtn" aria-label="Copy link">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/validation.js"></script>
