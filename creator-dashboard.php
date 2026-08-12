<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
require_login();

$uid = current_user_id();

// ---- My campaigns ----
$myCampaigns = [];
$stmt = $conn->prepare(
    "SELECT c.*, cat.name AS category_name,
     (SELECT COUNT(*) FROM donations d WHERE d.campaign_id = c.id) AS donor_count
     FROM campaigns c JOIN categories cat ON c.category_id = cat.id
     WHERE c.user_id = ? ORDER BY c.created_at DESC"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $myCampaigns[] = $row; }
$stmt->close();

// ---- Also show any personal donations this creator has made elsewhere ----
$myDonations = [];
$stmt = $conn->prepare(
    "SELECT d.amount, d.created_at, c.id AS campaign_id, c.title
     FROM donations d JOIN campaigns c ON d.campaign_id = c.id
     WHERE d.user_id = ? ORDER BY d.created_at DESC LIMIT 5"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $myDonations[] = $row; }
$stmt->close();

$totalRaised = array_sum(array_column($myCampaigns, 'raised_amount'));
$totalDonors = array_sum(array_column($myCampaigns, 'donor_count'));
$liveCampaigns = count(array_filter($myCampaigns, fn($c) => $c['status'] === 'Approved'));

$pageTitle = 'My Dashboard';
$active = 'dashboard';
$basePath = '';
include 'includes/header.php';
?>

<section>
  <div class="section-head center">
    <span class="role-badge creator" style="margin-bottom:10px;">🚀 Campaign Creator Account</span>
    <h2><?php echo h($_SESSION['user_name']); ?>'s Dashboard</h2>
    <p>Manage your campaigns, post updates, and track every donation.</p>
  </div>

  <div class="dash-stats">
    <div class="dash-stat-card"><div class="n"><?php echo count($myCampaigns); ?></div><div class="l">My Campaigns</div></div>
    <div class="dash-stat-card"><div class="n"><?php echo $liveCampaigns; ?></div><div class="l">Live Now</div></div>
    <div class="dash-stat-card"><div class="n"><?php echo money($totalRaised); ?></div><div class="l">Total Raised</div></div>
    <div class="dash-stat-card"><div class="n"><?php echo $totalDonors; ?></div><div class="l">Total Donors</div></div>
  </div>

  <div style="display:flex; justify-content:flex-end; max-width:1160px; margin: 0 auto 16px;">
    <a href="create-campaign.php" class="btn btn-primary btn-sm">+ Start a New Campaign</a>
  </div>

  <div class="table-wrap" style="max-width:1160px; margin: 0 auto 50px;">
    <table class="data-table">
      <thead>
        <tr><th>Campaign</th><th>Category</th><th>Raised / Goal</th><th>Donors</th><th>Deadline</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($myCampaigns)): ?>
          <tr><td colspan="7" style="text-align:center; padding:32px; color:var(--slate);">You haven't started a campaign yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($myCampaigns as $c): ?>
          <tr>
            <td><?php echo h($c['title']); ?></td>
            <td><?php echo h($c['category_name']); ?></td>
            <td><?php echo money($c['raised_amount']); ?> / <?php echo money($c['goal_amount']); ?></td>
            <td><?php echo (int)$c['donor_count']; ?></td>
            <td><?php echo date('d M Y', strtotime($c['deadline'])); ?></td>
            <td><span class="badge badge-<?php echo strtolower($c['status']); ?>"><?php echo h($c['status']); ?></span></td>
            <td>
              <a class="action-link confirm" href="campaign.php?id=<?php echo $c['id']; ?>">View</a>
              <a class="action-link" href="edit-campaign.php?id=<?php echo $c['id']; ?>" style="color:var(--slate);">Edit</a>
              <a class="action-link" href="manage-campaign.php?id=<?php echo $c['id']; ?>" style="color:var(--teal);">Manage</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($myDonations)): ?>
  <div style="max-width:1160px; margin: 0 auto;">
    <h3 style="margin-bottom:16px;">Donations I've Made</h3>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Campaign</th><th>Amount</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($myDonations as $d): ?>
            <tr>
              <td><a href="campaign.php?id=<?php echo $d['campaign_id']; ?>" style="color:var(--teal); font-weight:600;"><?php echo h($d['title']); ?></a></td>
              <td><?php echo money($d['amount']); ?></td>
              <td><?php echo date('d M Y', strtotime($d['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
