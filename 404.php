<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
http_response_code(404);
$pageTitle = 'Page Not Found';
$basePath = '';
include 'includes/header.php';
?>

<section style="padding: 90px 24px; text-align:center;">
  <div style="font-size:3.4rem; margin-bottom:6px;">🧭</div>
  <span class="eyebrow">Error 404</span>
  <h2 style="margin-top:10px;">This page wandered off</h2>
  <p style="color:var(--slate); max-width:44ch; margin: 12px auto 30px;">The page you're looking for doesn't exist, may have been moved, or the campaign may no longer be available.</p>
  <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
    <a href="index.php" class="btn btn-primary">Back to Home</a>
    <a href="campaigns.php" class="btn btn-outline">Explore Campaigns</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
