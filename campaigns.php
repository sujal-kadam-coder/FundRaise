<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

$categories = [];
$res = $conn->query('SELECT id, name, icon FROM categories ORDER BY name');
while ($row = $res->fetch_assoc()) { $categories[] = $row; }

$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$where = "WHERE c.status = 'Approved'";
$params = [];
$types = '';

if ($categoryFilter > 0) {
    $where .= ' AND c.category_id = ?';
    $params[] = $categoryFilter;
    $types .= 'i';
}
if ($search !== '') {
    $where .= ' AND (c.title LIKE ? OR c.short_description LIKE ?)';
    $like = "%$search%";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}

switch ($sort) {
    case 'ending_soon': $orderBy = ' c.deadline ASC'; break;
    case 'most_funded': $orderBy = ' c.raised_amount DESC'; break;
    case 'goal_high': $orderBy = ' c.goal_amount DESC'; break;
    default: $orderBy = ' c.created_at DESC';
}

// ---- Total matching count (for pagination) ----
$countStmt = $conn->prepare("SELECT COUNT(*) c FROM campaigns c $where");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalCount = (int)$countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

// ---- Pagination ----
$perPage = 9;
$totalPages = max(1, (int)ceil($totalCount / $perPage));
$page = max(1, (int)($_GET['page'] ?? 1));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT c.*, cat.name AS category_name FROM campaigns c
        JOIN categories cat ON c.category_id = cat.id
        $where ORDER BY $orderBy LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$campaigns = $stmt->get_result();

function pagination_url($base, $currentSort, $currentSearch, $currentCategory, $pageNum) {
    $params = [
        'sort' => $currentSort,
        'q' => $currentSearch,
        'category' => $currentCategory,
        'page' => $pageNum,
    ];
    return 'campaigns.php?' . http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== '0' && $v !== null));
}

$pageTitle = 'Explore Campaigns';
$active = 'campaigns';
$basePath = '';
include 'includes/header.php';
?>

<section style="padding-bottom: 30px;">
  <div class="section-head center">
    <span class="eyebrow">Explore</span>
    <h2>Every campaign here has been reviewed and approved</h2>
    <p><?php echo number_format($totalCount); ?> campaign<?php echo $totalCount === 1 ? '' : 's'; ?> found<?php echo $search !== '' ? ' for "' . h($search) . '"' : ''; ?></p>
  </div>

  <form method="GET" action="campaigns.php" class="filter-form" style="max-width:1160px; margin: 0 auto 20px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
    <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search campaigns…" style="flex:1; min-width:200px; padding:11px 14px; border:1.5px solid var(--line); border-radius:8px; font-family:'Inter',sans-serif;">
    <select name="category" style="padding:11px 14px; border:1.5px solid var(--line); border-radius:8px; font-family:'Inter',sans-serif;">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo $cat['icon']; ?> <?php echo h($cat['name']); ?></option>
      <?php endforeach; ?>
    </select>
    <select name="sort" style="padding:11px 14px; border:1.5px solid var(--line); border-radius:8px; font-family:'Inter',sans-serif;">
      <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
      <option value="ending_soon" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
      <option value="most_funded" <?php echo $sort === 'most_funded' ? 'selected' : ''; ?>>Most Funded</option>
      <option value="goal_high" <?php echo $sort === 'goal_high' ? 'selected' : ''; ?>>Highest Goal</option>
    </select>
    <button type="submit" class="btn btn-teal btn-sm">Filter</button>
    <a href="campaigns.php" class="btn btn-outline btn-sm">Reset</a>
  </form>

  <div class="campaign-grid">
    <?php if ($campaigns->num_rows === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">🔎</div>
        <h3>No campaigns found</h3>
        <p>No campaigns match your search. Try a different keyword, category, or sort order.</p>
        <a href="campaigns.php" class="btn btn-teal btn-sm">Reset Filters</a>
      </div>
    <?php endif; ?>
    <?php while ($c = $campaigns->fetch_assoc()): ?>
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
    <?php endwhile; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <nav class="pagination" style="max-width:1160px; margin: 36px auto 0; display:flex; justify-content:center; align-items:center; gap:8px; flex-wrap:wrap;">
    <?php if ($page > 1): ?>
      <a href="<?php echo pagination_url('campaigns.php', $sort, $search, $categoryFilter, $page - 1); ?>" class="page-btn">← Prev</a>
    <?php endif; ?>

    <?php
      $start = max(1, $page - 2);
      $end = min($totalPages, $page + 2);
      for ($p = $start; $p <= $end; $p++):
    ?>
      <a href="<?php echo pagination_url('campaigns.php', $sort, $search, $categoryFilter, $p); ?>" class="page-btn <?php echo $p === $page ? 'page-current' : ''; ?>"><?php echo $p; ?></a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
      <a href="<?php echo pagination_url('campaigns.php', $sort, $search, $categoryFilter, $page + 1); ?>" class="page-btn">Next →</a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
