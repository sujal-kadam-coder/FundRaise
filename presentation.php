<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

// ---- Platform stats for the 3D tour ----
$stats = ['campaigns' => 0, 'raised' => 0, 'donors' => 0];
$res = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(raised_amount),0) r FROM campaigns WHERE status = 'Approved'");
if ($row = $res->fetch_assoc()) { $stats['campaigns'] = $row['c']; $stats['raised'] = $row['r']; }
$res = $conn->query("SELECT COUNT(DISTINCT user_id) c FROM donations");
if ($row = $res->fetch_assoc()) { $stats['donors'] = $row['c']; }

// ---- Featured campaigns for the tour (highest % funded, live) ----
$featured = [];
$res = $conn->query(
    "SELECT c.*, cat.name AS category_name, cat.icon AS category_icon FROM campaigns c
     JOIN categories cat ON c.category_id = cat.id
     WHERE c.status = 'Approved' AND c.deadline >= CURDATE()
     ORDER BY (c.raised_amount / c.goal_amount) DESC LIMIT 3"
);
while ($row = $res->fetch_assoc()) { $featured[] = $row; }

// ---- Steps for the "how it works" section ----
$steps = [
    ['icon' => '📝', 'title' => 'Start your campaign', 'text' => 'Share your story, set a goal and deadline. Our team reviews it before it goes public.'],
    ['icon' => '📣', 'title' => 'Share it widely', 'text' => 'Spread the word so donors can find your cause and contribute directly on the platform.'],
    ['icon' => '📈', 'title' => 'Track your progress', 'text' => 'Watch your progress bar grow, post updates, and thank donors — all from your dashboard.'],
];

$pageTitle = '3D Tour';
$basePath = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="An interactive 3D tour of FundRaise — the crowdfunding platform where genuine causes reach generous people.">
<title>FundRaise — Interactive 3D Tour</title>
<link rel="icon" type="image/png" href="images/favicon-32.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/presentation.css">
</head>
<body class="tour-body">

<canvas id="tour-scene" aria-hidden="true"></canvas>
<div class="tour-vignette" aria-hidden="true"></div>

<header class="tour-bar">
  <a href="index.php" class="tour-brand">Fund<span>Raise</span></a>
  <nav class="tour-nav">
    <a href="campaigns.php">Explore Campaigns</a>
    <a href="index.php" class="tour-exit">Exit Tour →</a>
  </nav>
</header>

<div class="tour-progress" aria-hidden="true"><div class="tour-progress-fill"></div></div>

<main class="tour-main">

  <section class="tour-section tour-intro" id="tour-intro">
    <div class="tour-inner">
      <span class="tour-eyebrow">Interactive 3D Tour</span>
      <h1 class="tour-title">Fund what matters — <em>together</em>.</h1>
      <p class="tour-lead">FundRaise connects genuine causes — medical emergencies, community projects, creative ideas and startups — with people who want to help.</p>
      <div class="tour-actions">
        <a href="campaigns.php" class="tour-btn tour-btn-primary">Explore Campaigns</a>
        <a href="<?php echo is_logged_in() ? 'create-campaign.php' : 'register.php'; ?>" class="tour-btn tour-btn-ghost">Start a Campaign</a>
      </div>
      <div class="tour-scroll-hint">
        <span class="tour-scroll-mouse"><span></span></span>
        <span>Scroll to explore</span>
      </div>
    </div>
  </section>

  <section class="tour-section" id="tour-stats">
    <div class="tour-inner">
      <span class="tour-eyebrow">The Numbers</span>
      <h2 class="tour-subtitle">What the community has built together</h2>
      <div class="tour-stats">
        <div class="tour-stat-card">
          <b class="tour-count" data-target="<?php echo (int)$stats['campaigns']; ?>" data-suffix="+">0</b>
          <span>Active Campaigns</span>
        </div>
        <div class="tour-stat-card">
          <b class="tour-count" data-prefix="₹" data-target="<?php echo (int)$stats['raised']; ?>">0</b>
          <span>Raised So Far</span>
        </div>
        <div class="tour-stat-card">
          <b class="tour-count" data-target="<?php echo (int)$stats['donors']; ?>" data-suffix="+">0</b>
          <span>Generous Donors</span>
        </div>
      </div>
    </div>
  </section>

  <section class="tour-section" id="tour-how">
    <div class="tour-inner">
      <span class="tour-eyebrow">How It Works</span>
      <h2 class="tour-subtitle">From idea to funded, in three steps</h2>
      <div class="tour-steps">
        <?php foreach ($steps as $i => $step): ?>
          <div class="tour-step">
            <div class="tour-step-num"><?php echo $i + 1; ?></div>
            <div class="tour-step-icon"><?php echo $step['icon']; ?></div>
            <h3><?php echo h($step['title']); ?></h3>
            <p><?php echo h($step['text']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="tour-section" id="tour-featured">
    <div class="tour-inner">
      <span class="tour-eyebrow">Live Right Now</span>
      <h2 class="tour-subtitle">Campaigns close to their goal</h2>
      <div class="tour-cards">
        <?php foreach ($featured as $c): ?>
          <?php $pct = progress_percent($c['raised_amount'], $c['goal_amount']); ?>
          <a href="campaign.php?id=<?php echo $c['id']; ?>" class="tour-card tour-card-<?php echo h($c['cover_color']); ?>">
            <span class="tour-card-emoji"><?php echo h($c['category_icon']); ?></span>
            <span class="tour-card-cat"><?php echo h($c['category_name']); ?></span>
            <h3><?php echo h($c['title']); ?></h3>
            <div class="tour-card-bar"><span style="width:<?php echo $pct; ?>%;"></span></div>
            <div class="tour-card-meta">
              <b><?php echo money($c['raised_amount']); ?></b>
              <span><?php echo $pct; ?>% of <?php echo money($c['goal_amount']); ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="tour-section" id="tour-cta">
    <div class="tour-inner">
      <span class="tour-eyebrow">Your Turn</span>
      <h2 class="tour-title">Have a cause worth funding?</h2>
      <p class="tour-lead">It takes less than five minutes to start a campaign — and every submission is reviewed by our team before going live.</p>
      <div class="tour-actions">
        <a href="<?php echo is_logged_in() ? 'create-campaign.php' : 'register.php'; ?>" class="tour-btn tour-btn-primary">Start a Campaign</a>
        <a href="about.php" class="tour-btn tour-btn-ghost">About Us</a>
      </div>
    </div>
  </section>

</main>

<script>
window.tourData = {
  stats: <?php echo json_encode(['campaigns' => (int)$stats['campaigns'], 'raised' => (float)$stats['raised'], 'donors' => (int)$stats['donors']]); ?>,
  featured: <?php echo json_encode(array_map(fn($c) => ['title' => $c['title'], 'pct' => progress_percent($c['raised_amount'], $c['goal_amount'])], $featured)); ?>
};
</script>
<script src="https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js"></script>
<script src="js/presentation.js"></script>
</body>
</html>
