<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
require_login();

$uid = current_user_id();

// ---- My donations ----
$myDonations = [];
$stmt = $conn->prepare(
    "SELECT d.amount, d.message, d.created_at, c.id AS campaign_id, c.title, c.cover_color
     FROM donations d JOIN campaigns c ON d.campaign_id = c.id
     WHERE d.user_id = ? ORDER BY d.created_at DESC"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $myDonations[] = $row; }
$stmt->close();

$totalDonated = array_sum(array_column($myDonations, 'amount'));
$campaignsSupported = count(array_unique(array_column($myDonations, 'campaign_id')));

// ---- Suggested campaigns (not yet supported by this donor) ----
$suggested = [];
$supportedIds = array_unique(array_column($myDonations, 'campaign_id'));
$sql = "SELECT c.*, cat.name AS category_name FROM campaigns c
        JOIN categories cat ON c.category_id = cat.id
        WHERE c.status = 'Approved' AND c.deadline >= CURDATE()";
if (!empty($supportedIds)) {
    $sql .= ' AND c.id NOT IN (' . implode(',', array_map('intval', $supportedIds)) . ')';
}
$sql .= ' ORDER BY c.created_at DESC LIMIT 3';
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) { $suggested[] = $row; }

$pageTitle = 'My Dashboard';
$active = 'dashboard';
$basePath = '';
include 'includes/header.php';
?>

<section>
  <div class="section-head center">
    <span class="role-badge donor" style="margin-bottom:10px;">🤲 Donor Account</span>
    <h2><?php echo h($_SESSION['user_name']); ?>'s Dashboard</h2>
    <p>Track every cause you've supported, all in one place.</p>
  </div>

  <div class="dash-stats" style="grid-template-columns: repeat(3, 1fr); max-width: 820px;">
    <div class="dash-stat-card"><div class="n"><?php echo money($totalDonated); ?></div><div class="l">Total Donated</div></div>
    <div class="dash-stat-card"><div class="n"><?php echo count($myDonations); ?></div><div class="l">Donations Made</div></div>
    <div class="dash-stat-card"><div class="n"><?php echo $campaignsSupported; ?></div><div class="l">Campaigns Supported</div></div>
  </div>

  <div style="max-width:1160px; margin: 0 auto 50px;">
    <h3 style="margin-bottom:16px;">My Donation History</h3>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Campaign</th><th>Amount</th><th>Message</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php if (empty($myDonations)): ?>
            <tr><td colspan="4" style="text-align:center; padding:32px; color:var(--slate);">You haven't donated to any campaign yet — explore causes below.</td></tr>
          <?php endif; ?>
          <?php foreach ($myDonations as $d): ?>
            <tr>
              <td><a href="campaign.php?id=<?php echo $d['campaign_id']; ?>" style="color:var(--teal); font-weight:600;"><?php echo h($d['title']); ?></a></td>
              <td><?php echo money($d['amount']); ?></td>
              <td><?php echo $d['message'] ? h($d['message']) : '—'; ?></td>
              <td><?php echo date('d M Y', strtotime($d['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (!empty($suggested)): ?>
  <div class="section-head">
    <div>
      <span class="eyebrow">Discover</span>
      <h2>Causes you might like</h2>
    </div>
    <a href="campaigns.php" class="btn btn-outline btn-sm">Explore All</a>
  </div>
  <div class="campaign-grid">
    <?php foreach ($suggested as $c): ?>
      <a href="campaign.php?id=<?php echo $c['id']; ?>" class="campaign-card">
        <?php echo cover_html($c, '', '<span class="card-badge">' . progress_percent($c['raised_amount'], $c['goal_amount']) . '% funded</span>'); ?>
        <div class="card-body">
          <span class="card-cat"><?php echo h($c['category_name']); ?></span>
          <h3><?php echo h($c['title']); ?></h3>
          <p class="desc"><?php echo h(excerpt($c['short_description'], 90)); ?></p>
          <div class="progress-bar"><div class="progress-fill" style="width:<?php echo progress_percent($c['raised_amount'], $c['goal_amount']); ?>%;"></div></div>
          <div class="card-footer"><span><b><?php echo money($c['raised_amount']); ?></b> raised</span><span><?php echo days_left($c['deadline']); ?> days left</span></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div style="max-width:1160px; margin: 50px auto 0; text-align:center; background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:32px;">
    <p style="color:var(--slate); margin-bottom:12px;">Have a cause of your own you'd like to raise funds for?</p>
    <a href="create-campaign.php" class="btn btn-teal btn-sm">Start a Campaign as a Creator</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
