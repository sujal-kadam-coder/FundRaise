<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

$stats = ['campaigns' => 0, 'raised' => 0, 'donors' => 0];
$res = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(raised_amount),0) r FROM campaigns WHERE status = 'Approved'");
if ($row = $res->fetch_assoc()) { $stats['campaigns'] = $row['c']; $stats['raised'] = $row['r']; }
$res = $conn->query("SELECT COUNT(DISTINCT user_id) c FROM donations");
if ($row = $res->fetch_assoc()) { $stats['donors'] = $row['c']; }

$pageTitle = 'About Us';
$active = 'about';
$basePath = '';
include 'includes/header.php';
?>

<section class="hero" style="padding-bottom:64px;">
  <div class="container" style="text-align:center; max-width:720px;">
    <span class="eyebrow" style="color:var(--amber);">About FundRaise</span>
    <h1 style="margin-top:14px;">Built so genuine causes don't get lost in the noise.</h1>
    <p class="lead" style="margin:0 auto;">We started FundRaise to make it simple for anyone — a family facing a medical emergency, a community rebuilding after a disaster, a founder with a good idea — to reach people willing to help.</p>
  </div>
</section>

<section>
  <div class="split">
    <div>
      <span class="eyebrow">Our Mission</span>
      <h2 style="margin-top:10px;">Transparency first, always</h2>
      <p style="margin-top:16px; color:var(--slate);">Every campaign on FundRaise is manually reviewed by our team before it goes live. We believe donors deserve to know exactly where their money is going, and creators deserve a platform that doesn't bury their story under fees and friction.</p>
      <p style="margin-top:14px; color:var(--slate);">We're a small, mission-driven team who built this platform to solve a problem we saw again and again: good causes struggling to reach the people who'd gladly help, if only they knew.</p>
    </div>
    <div class="steps-grid" style="grid-template-columns: 1fr 1fr; gap:16px;">
      <div class="step-card"><div class="step-num">🎯</div><h3>Focused</h3><p>We only host campaigns that pass a real review — no spam, no scams.</p></div>
      <div class="step-card"><div class="step-num">🔍</div><h3>Transparent</h3><p>Every rupee raised is visible, with progress and creator updates public.</p></div>
      <div class="step-card"><div class="step-num">🤝</div><h3>Community-first</h3><p>We're built for individuals and small causes, not just big organizations.</p></div>
      <div class="step-card"><div class="step-num">⚡</div><h3>Simple</h3><p>Starting or supporting a campaign takes minutes, not hours.</p></div>
    </div>
  </div>
</section>

<section style="background: var(--ink); color: #fff; text-align:center;">
  <div class="container">
    <span class="eyebrow">By The Numbers</span>
    <h2 style="color:#fff; margin-top:10px;">What our community has built together</h2>
    <div class="hero-stats" style="justify-content:center; margin-top:36px;">
      <div class="stat"><b><?php echo (int)$stats['campaigns']; ?>+</b><span>Active Campaigns</span></div>
      <div class="stat"><b><?php echo money($stats['raised']); ?></b><span>Raised So Far</span></div>
      <div class="stat"><b><?php echo (int)$stats['donors']; ?>+</b><span>Generous Donors</span></div>
    </div>
  </div>
</section>

<section style="text-align:center;">
  <span class="eyebrow">Get Involved</span>
  <h2 style="margin-top:10px;">Ready to be part of it?</h2>
  <div class="hero-actions" style="justify-content:center; margin-top:20px;">
    <a href="campaigns.php" class="btn btn-primary">Explore Campaigns</a>
    <a href="<?php echo is_logged_in() ? 'create-campaign.php' : 'register.php'; ?>" class="btn btn-outline">Start a Campaign</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
