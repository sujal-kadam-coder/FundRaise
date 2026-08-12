<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare(
    "SELECT c.*, cat.name AS category_name FROM campaigns c
     JOIN categories cat ON c.category_id = cat.id
     WHERE c.id = ? AND c.user_id = ?"
);
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$campaign) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    $basePath = '';
    include 'includes/header.php';
    echo '<section style="text-align:center;"><h2>Campaign not found</h2><p style="margin-top:10px;">You can only manage campaigns you created.</p><p style="margin-top:10px;"><a href="dashboard.php" style="color:var(--teal); font-weight:600;">← Back to dashboard</a></p></section>';
    include 'includes/footer.php';
    exit;
}

// ---- Close this campaign (creator-initiated) ----
if (isset($_GET['action']) && $_GET['action'] === 'close' && $campaign['status'] === 'Approved') {
    $stmt = $conn->prepare('UPDATE campaigns SET status = "Closed" WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $stmt->close();
    $campaign['status'] = 'Closed';
    header('Location: manage-campaign.php?id=' . $id . '&closed=1');
    exit;
}

// ---- Post a new update ----
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || strlen($title) < 5) {
        $errors['title'] = 'Give your update a short title (min 5 characters).';
    }
    if ($content === '' || strlen($content) < 10) {
        $errors['content'] = 'Write at least a sentence or two.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('INSERT INTO campaign_updates (campaign_id, title, content) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $id, $title, $content);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors['general'] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

// ---- Existing updates ----
$updates = [];
$stmt = $conn->prepare('SELECT title, content, created_at FROM campaign_updates WHERE campaign_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $updates[] = $row; }
$stmt->close();

// ---- Donor list ----
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

$pageTitle = 'Manage: ' . $campaign['title'];
$basePath = '';
include 'includes/header.php';
?>

<section style="padding-bottom:30px;">
  <div class="section-head">
    <div>
      <span class="eyebrow"><?php echo h($campaign['category_name']); ?> · <span class="badge badge-<?php echo strtolower($campaign['status']); ?>"><?php echo h($campaign['status']); ?></span></span>
      <h2><?php echo h($campaign['title']); ?></h2>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <?php if ($campaign['status'] === 'Approved'): ?>
        <a class="btn btn-outline btn-sm" href="edit-campaign.php?id=<?php echo $id; ?>">✏️ Edit Campaign</a>
      <?php endif; ?>
      <a href="campaign.php?id=<?php echo $id; ?>" class="btn btn-outline btn-sm">View Public Page</a>
      <?php if ($campaign['status'] === 'Approved'): ?>
        <a class="btn btn-outline btn-sm" style="color:var(--danger); border-color:var(--danger);" href="manage-campaign.php?id=<?php echo $id; ?>&action=close" onclick="return confirm('Close this campaign? It will stop accepting donations and no longer appear in public listings.');">Close Campaign</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (isset($_GET['closed'])): ?>
    <div class="alert alert-success" style="max-width:1160px; margin:0 auto 28px;">This campaign has been closed and is no longer accepting donations.</div>
  <?php endif; ?>
  <?php if ($campaign['status'] === 'Closed'): ?>
    <div class="alert alert-error" style="max-width:1160px; margin:0 auto 28px;">This campaign is closed. It won't accept donations or appear in public listings. Contact support if you'd like to reopen it.</div>
  <?php endif; ?>

  <div class="dash-stats" style="margin-bottom:44px;">
    <div class="dash-stat-card"><div class="n"><?php echo money($campaign['raised_amount']); ?></div><div class="l">Raised</div></div>
    <div class="dash-stat-card"><div class="n"><?php echo progress_percent($campaign['raised_amount'], $campaign['goal_amount']); ?>%</div><div class="l">Of Goal</div></div>
    <div class="dash-stat-card"><div class="n"><?php echo count($donors); ?></div><div class="l">Donors</div></div>
    <div class="dash-stat-card"><div class="n"><?php echo days_left($campaign['deadline']); ?></div><div class="l">Days Left</div></div>
  </div>

  <div class="detail-grid">
    <div>
      <h3 style="margin-bottom:16px;">Post an Update</h3>
      <div class="form-card" style="margin-bottom:40px;">
        <?php if ($success): ?>
          <div class="alert alert-success">Update posted! Your donors will see it on the campaign page.</div>
        <?php endif; ?>
        <?php if (!empty($errors['general'])): ?>
          <div class="alert alert-error"><?php echo h($errors['general']); ?></div>
        <?php endif; ?>
        <form method="POST" action="manage-campaign.php?id=<?php echo $id; ?>" novalidate>
          <div class="field <?php echo isset($errors['title']) ? 'has-error' : ''; ?>" data-field="title">
            <label for="title">Update Title</label>
            <input type="text" id="title" name="title" placeholder="e.g. Halfway to our goal!">
            <span class="error-msg"><?php echo $errors['title'] ?? ''; ?></span>
          </div>
          <div class="field <?php echo isset($errors['content']) ? 'has-error' : ''; ?>" data-field="content">
            <label for="content">Update Details</label>
            <textarea id="content" name="content" rows="4" placeholder="Share progress, thank donors, or explain next steps."></textarea>
            <span class="error-msg"><?php echo $errors['content'] ?? ''; ?></span>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Post Update</button>
        </form>
      </div>

      <h3 style="margin-bottom:16px;">Past Updates</h3>
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

    <div>
      <h3 style="margin-bottom:16px;">Donors (<?php echo count($donors); ?>)</h3>
      <div class="table-wrap">
        <?php if (empty($donors)): ?>
          <p style="color:var(--slate); padding:20px;">No donations yet.</p>
        <?php else: ?>
          <?php foreach ($donors as $d): ?>
            <div class="donor-item" style="padding:12px 16px;">
              <div>
                <div class="name"><?php echo $d['is_anonymous'] ? 'Anonymous Donor' : h($d['full_name']); ?></div>
                <?php if (!empty($d['message'])): ?><div class="msg">"<?php echo h($d['message']); ?>"</div><?php endif; ?>
              </div>
              <div class="amt"><?php echo money($d['amount']); ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
