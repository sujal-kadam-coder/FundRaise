<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

// ---- Platform-wide stats for hero ----
$stats = ['campaigns' => 0, 'raised' => 0, 'donors' => 0];
$res = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(raised_amount),0) r FROM campaigns WHERE status = 'Approved'");
if ($row = $res->fetch_assoc()) { $stats['campaigns'] = $row['c']; $stats['raised'] = $row['r']; }
$res = $conn->query("SELECT COUNT(DISTINCT user_id) c FROM donations");
if ($row = $res->fetch_assoc()) { $stats['donors'] = $row['c']; }

// ---- Categories ----
$categories = [];
$res = $conn->query('SELECT id, name, icon FROM categories ORDER BY name');
while ($row = $res->fetch_assoc()) { $categories[] = $row; }

// ---- Featured campaigns (highest raised, still approved & open) ----
$featured = [];
$res = $conn->query(
    "SELECT c.*, cat.name AS category_name FROM campaigns c
     JOIN categories cat ON c.category_id = cat.id
     WHERE c.status = 'Approved' AND c.deadline >= CURDATE()
     ORDER BY (c.raised_amount / c.goal_amount) DESC LIMIT 3"
);
while ($row = $res->fetch_assoc()) { $featured[] = $row; }

// ---- One example for the hero preview card ----
$heroPreview = $featured[0] ?? null;

// ---- Recently launched (newest approved campaigns) ----
$recent = [];
$res = $conn->query(
    "SELECT c.*, cat.name AS category_name FROM campaigns c
     JOIN categories cat ON c.category_id = cat.id
     WHERE c.status = 'Approved' AND c.deadline >= CURDATE()
     ORDER BY c.created_at DESC LIMIT 6"
);
while ($row = $res->fetch_assoc()) { $recent[] = $row; }

$pageTitle = 'Home';
$active = 'home';
$basePath = '';
include 'includes/header.php';
?>

<section class="hero" style="padding-bottom: 90px;">
  <div class="hero-grid">
    <div>
      <span class="eyebrow" style="color: var(--amber);">Crowdfunding, done transparently</span>
      <h1>Fund what matters — <em>together</em>.</h1>
      <p class="lead">FundRaise connects genuine causes — medical emergencies, community projects, creative ideas, and startups — with people who want to help. Every campaign is reviewed before it goes live.</p>
      <div class="hero-actions">
        <a href="campaigns.php" class="btn btn-primary">Explore Campaigns</a>
        <a href="<?php echo is_logged_in() ? 'create-campaign.php' : 'register.php'; ?>" class="btn btn-outline" style="background:transparent; color:#fff; border-color:rgba(255,255,255,0.4);">Start a Campaign</a>
      </div>
      <div class="hero-stats">
        <div class="stat"><b><?php echo (int)$stats['campaigns']; ?>+</b><span>Active Campaigns</span></div>
        <div class="stat"><b><?php echo money($stats['raised']); ?></b><span>Raised So Far</span></div>
        <div class="stat"><b><?php echo (int)$stats['donors']; ?>+</b><span>Generous Donors</span></div>
      </div>
    </div>
    <?php if ($heroPreview): ?>
      <div class="preview-card">
        <?php echo cover_html($heroPreview, 'preview-cover'); ?>
        <div class="preview-body">
          <span class="card-cat"><?php echo h($heroPreview['category_name']); ?></span>
          <h3><?php echo h($heroPreview['title']); ?></h3>
          <div class="progress-bar"><div class="progress-fill" style="width:<?php echo progress_percent($heroPreview['raised_amount'], $heroPreview['goal_amount']); ?>%;"></div></div>
          <div class="progress-meta">
            <span><b><?php echo money($heroPreview['raised_amount']); ?></b> raised</span>
            <span><?php echo progress_percent($heroPreview['raised_amount'], $heroPreview['goal_amount']); ?>% of <?php echo money($heroPreview['goal_amount']); ?></span>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<section>
  <div class="section-head center">
    <span class="eyebrow">Browse By Category</span>
    <h2>Find a cause you care about</h2>
  </div>
  <div class="category-strip">
    <?php foreach ($categories as $cat): ?>
      <a href="campaigns.php?category=<?php echo $cat['id']; ?>" class="category-chip"><?php echo $cat['icon']; ?> <?php echo h($cat['name']); ?></a>
    <?php endforeach; ?>
  </div>
</section>

<section style="background: var(--panel); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);">
  <div class="section-head">
    <div>
      <span class="eyebrow">Trending Now</span>
      <h2>Campaigns close to their goal</h2>
    </div>
    <a href="campaigns.php" class="btn btn-outline btn-sm">View All Campaigns</a>
  </div>
  <div class="campaign-grid">
    <?php foreach ($featured as $c): ?>
      <a href="campaign.php?id=<?php echo $c['id']; ?>" class="campaign-card">
        <?php echo cover_html($c, '', '<span class="card-badge">' . progress_percent($c['raised_amount'], $c['goal_amount']) . '% funded</span>'); ?>
        <div class="card-body">
          <span class="card-cat"><?php echo h($c['category_name']); ?></span>
          <h3><?php echo h($c['title']); ?></h3>
          <p class="desc"><?php echo h(excerpt($c['short_description'], 90)); ?></p>
          <div class="progress-bar"><div class="progress-fill" style="width:<?php echo progress_percent($c['raised_amount'], $c['goal_amount']); ?>%;"></div></div>
          <div class="card-footer">
            <span><b><?php echo money($c['raised_amount']); ?></b> raised</span>
            <span><?php echo days_left($c['deadline']); ?> days left</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (empty($featured)): ?>
      <div class="empty-state">
        <div class="empty-icon">🎯</div>
        <h3>No campaigns yet</h3>
        <p>Be the first to start a campaign — every submission is reviewed before going live.</p>
        <a href="<?php echo is_logged_in() ? 'create-campaign.php' : 'register.php'; ?>" class="btn btn-teal btn-sm">Start a Campaign</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<section>
  <div class="section-head center">
    <span class="eyebrow">How It Works</span>
    <h2>From idea to funded, in three steps</h2>
  </div>
  <div class="steps-grid">
    <div class="step-card">
      <div class="step-num">1</div>
      <h3>Start your campaign</h3>
      <p>Share your story, set a goal amount and deadline. Our team reviews it before it goes public.</p>
    </div>
    <div class="step-card">
      <div class="step-num">2</div>
      <h3>Share it widely</h3>
      <p>Spread the word so donors can find your cause and contribute directly through the platform.</p>
    </div>
    <div class="step-card">
      <div class="step-num">3</div>
      <h3>Track your progress</h3>
      <p>Watch your progress bar grow, post updates, and thank donors — all from your dashboard.</p>
    </div>
  </div>
</section>

<section>
  <div class="section-head">
    <div>
      <span class="eyebrow">New This Week</span>
      <h2>Fresh causes, just launched</h2>
    </div>
    <a href="campaigns.php?sort=newest" class="btn btn-outline btn-sm">See Newest</a>
  </div>
  <div class="campaign-grid">
    <?php if (empty($recent)): ?>
      <div class="empty-state">
        <div class="empty-icon">🚀</div>
        <h3>No campaigns launched yet</h3>
        <p>Be the first to launch a cause on FundRaise today.</p>
        <a href="<?php echo is_logged_in() ? 'create-campaign.php' : 'register.php'; ?>" class="btn btn-teal btn-sm">Start a Campaign</a>
      </div>
    <?php endif; ?>
    <?php foreach ($recent as $c): ?>
      <a href="campaign.php?id=<?php echo $c['id']; ?>" class="campaign-card">
        <?php echo cover_html($c, '', '<span class="card-badge">' . progress_percent($c['raised_amount'], $c['goal_amount']) . '% funded</span>'); ?>
        <div class="card-body">
          <span class="card-cat"><?php echo h($c['category_name']); ?></span>
          <h3><?php echo h($c['title']); ?></h3>
          <p class="desc"><?php echo h(excerpt($c['short_description'], 90)); ?></p>
          <div class="progress-bar"><div class="progress-fill" style="width:<?php echo progress_percent($c['raised_amount'], $c['goal_amount']); ?>%;"></div></div>
          <div class="card-footer">
            <span><b><?php echo money($c['raised_amount']); ?></b> raised</span>
            <span><?php echo days_left($c['deadline']); ?> days left</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section style="text-align:center; background: var(--ink); color: #fff; margin: 0 24px 80px; border-radius: 12px; padding: 64px 24px;">
  <h2 style="color: #fff;">Have a cause worth funding?</h2>
  <p style="max-width:48ch; margin: 14px auto 28px; color:#c3d3d0;">It takes less than five minutes to start a campaign — and every submission is reviewed by our team before going live.</p>
  <a href="<?php echo is_logged_in() ? 'create-campaign.php' : 'register.php'; ?>" class="btn btn-primary">Start a Campaign</a>
</section>

<?php include 'includes/footer.php'; ?>
