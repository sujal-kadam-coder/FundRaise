<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare('SELECT * FROM campaigns WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$campaign) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    $basePath = '';
    include 'includes/header.php';
    echo '<section style="text-align:center;"><h2>Campaign not found</h2><p style="margin-top:10px;">You can only edit campaigns you created.</p><p style="margin-top:10px;"><a href="dashboard.php" style="color:var(--teal); font-weight:600;">← Back to dashboard</a></p></section>';
    include 'includes/footer.php';
    exit;
}

$categories = [];
$res = $conn->query('SELECT id, name, icon FROM categories ORDER BY name');
while ($row = $res->fetch_assoc()) { $categories[] = $row; }

$errors = [];
$success = false;
$old = [
    'title' => $campaign['title'],
    'category_id' => $campaign['category_id'],
    'short_description' => $campaign['short_description'],
    'story' => $campaign['story'],
    'goal_amount' => $campaign['goal_amount'],
    'deadline' => $campaign['deadline'],
    'cover_color' => $campaign['cover_color'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['title', 'short_description', 'story', 'goal_amount', 'deadline'] as $key) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $old['category_id'] = (int)($_POST['category_id'] ?? 0);
    $old['cover_color'] = in_array($_POST['cover_color'] ?? '', ['teal', 'coral', 'amber']) ? $_POST['cover_color'] : 'teal';

    if ($old['title'] === '' || strlen($old['title']) < 10) {
        $errors['title'] = 'Title should be at least 10 characters.';
    }
    if ($old['category_id'] <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }
    if ($old['short_description'] === '' || strlen($old['short_description']) < 20) {
        $errors['short_description'] = 'Give a short summary (min 20 characters).';
    } elseif (strlen($old['short_description']) > 200) {
        $errors['short_description'] = 'Keep it under 200 characters.';
    }
    if ($old['story'] === '' || strlen($old['story']) < 50) {
        $errors['story'] = 'Tell your full story (min 50 characters).';
    }
    $goal = (float)$old['goal_amount'];
    if ($goal < 1000) {
        $errors['goal_amount'] = 'Goal must be at least ₹1,000.';
    } elseif ($goal > 10000000) {
        $errors['goal_amount'] = 'Goal cannot exceed ₹1,00,00,000.';
    }
    $today = date('Y-m-d');
    if ($old['deadline'] === '' || $old['deadline'] < $today) {
        $errors['deadline'] = 'Please choose today or a future date.';
    }

    $coverImagePath = $campaign['cover_image'];
    if (!empty($_FILES['cover_image']['name'])) {
        $file = $_FILES['cover_image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['cover_image'] = 'There was a problem uploading the image.';
        } elseif (!isset($allowed[$mime])) {
            $errors['cover_image'] = 'Please upload a JPG, PNG, or WEBP image.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors['cover_image'] = 'Image must be smaller than 5MB.';
        } else {
            $filename = 'campaign_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
            $destDir = __DIR__ . '/uploads/campaigns/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                $coverImagePath = 'uploads/campaigns/' . $filename;
            } else {
                $errors['cover_image'] = 'Could not save the uploaded image. Please try again.';
            }
        }
    }

    if (empty($errors)) {
        $resubmit = isset($_POST['resubmit']) && $campaign['status'] === 'Rejected';
        $newStatus = $resubmit ? 'Pending' : $campaign['status'];
        $stmt = $conn->prepare(
            "UPDATE campaigns
             SET category_id = ?, title = ?, short_description = ?, story = ?,
                 goal_amount = ?, deadline = ?, cover_color = ?, cover_image = ?, status = ?
             WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param('isssdssssii', $old['category_id'], $old['title'], $old['short_description'], $old['story'], $goal, $old['deadline'], $old['cover_color'], $coverImagePath, $newStatus, $id, $uid);
        if ($stmt->execute()) {
            $success = true;
            $campaign['title'] = $old['title'];
            $campaign['category_id'] = $old['category_id'];
            $campaign['short_description'] = $old['short_description'];
            $campaign['story'] = $old['story'];
            $campaign['goal_amount'] = $goal;
            $campaign['deadline'] = $old['deadline'];
            $campaign['cover_color'] = $old['cover_color'];
            $campaign['cover_image'] = $coverImagePath;
            $campaign['status'] = $newStatus;
        } else {
            $errors['general'] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Edit: ' . $campaign['title'];
$basePath = '';
include 'includes/header.php';
?>

<section style="padding-bottom:30px;">
  <div class="section-head">
    <div>
      <span class="eyebrow">Edit Campaign · <span class="badge badge-<?php echo strtolower($campaign['status']); ?>"><?php echo h($campaign['status']); ?></span></span>
      <h2><?php echo h($campaign['title']); ?></h2>
      <p>Update your campaign details. <?php if ($campaign['status'] === 'Rejected'): ?>Your changes will be sent back for review before it goes live again.<?php else: ?>Changes appear on the public page right away.<?php endif; ?></p>
    </div>
    <a href="campaign.php?id=<?php echo $id; ?>" class="btn btn-outline btn-sm">View Public Page</a>
  </div>

  <div class="form-card" style="max-width:760px; margin:0 auto;">
    <?php if ($success): ?>
      <div class="alert alert-success">Changes saved successfully. <a href="campaign.php?id=<?php echo $id; ?>" style="color:inherit; font-weight:600; text-decoration:underline;">View campaign →</a></div>
    <?php endif; ?>
    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error"><?php echo h($errors['general']); ?></div>
    <?php endif; ?>

    <form method="POST" action="edit-campaign.php?id=<?php echo $id; ?>" id="campaignForm" enctype="multipart/form-data" novalidate>
      <div class="field <?php echo isset($errors['title']) ? 'has-error' : ''; ?>" data-field="title">
        <label for="title">Campaign Title</label>
        <input type="text" id="title" name="title" value="<?php echo h($old['title']); ?>" placeholder="e.g. Help Rebuild Our Village School">
        <span class="error-msg"><?php echo $errors['title'] ?? ''; ?></span>
      </div>

      <div class="field <?php echo isset($errors['cover_image']) ? 'has-error' : ''; ?>" data-field="cover_image">
        <label for="cover_image">Cover Photo <span style="font-weight:400; color:var(--slate);">(optional — JPG/PNG/WEBP, max 5MB)</span></label>
        <?php if (!empty($campaign['cover_image'])): ?>
          <div class="current-cover">
            <img src="<?php echo h($campaign['cover_image']); ?>" alt="Current cover">
            <span>Current cover photo</span>
          </div>
        <?php endif; ?>
        <div class="upload-drop" id="uploadDrop">
          <img id="uploadPreview" style="display:none;" alt="Preview">
          <div id="uploadPlaceholder">
            <span class="upload-icon">📷</span>
            <span><?php echo !empty($campaign['cover_image']) ? 'Click to replace the photo, or drag one here' : 'Click to choose a photo, or drag one here'; ?></span>
          </div>
        </div>
        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
        <span class="error-msg"><?php echo $errors['cover_image'] ?? ''; ?></span>
      </div>

      <div class="form-row">
        <div class="field <?php echo isset($errors['category_id']) ? 'has-error' : ''; ?>" data-field="category_id">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id">
            <option value="" disabled <?php echo $old['category_id'] === 0 ? 'selected' : ''; ?>>Choose a category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo (int)$old['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo $cat['icon']; ?> <?php echo h($cat['name']); ?></option>
            <?php endforeach; ?>
          </select>
          <span class="error-msg"><?php echo $errors['category_id'] ?? ''; ?></span>
        </div>
        <div class="field">
          <label for="cover_color">Fallback Color <span style="font-weight:400; color:var(--slate);">(used only if no photo)</span></label>
          <select id="cover_color" name="cover_color">
            <option value="teal" <?php echo $old['cover_color'] === 'teal' ? 'selected' : ''; ?>>Teal</option>
            <option value="coral" <?php echo $old['cover_color'] === 'coral' ? 'selected' : ''; ?>>Coral</option>
            <option value="amber" <?php echo $old['cover_color'] === 'amber' ? 'selected' : ''; ?>>Amber</option>
          </select>
        </div>
      </div>

      <div class="field <?php echo isset($errors['short_description']) ? 'has-error' : ''; ?>" data-field="short_description">
        <label for="short_description">Short Summary <span style="font-weight:400; color:var(--slate);">(max 200 characters)</span></label>
        <input type="text" id="short_description" name="short_description" value="<?php echo h($old['short_description']); ?>" placeholder="One sentence describing your cause">
        <span class="error-msg"><?php echo $errors['short_description'] ?? ''; ?></span>
      </div>

      <div class="field <?php echo isset($errors['story']) ? 'has-error' : ''; ?>" data-field="story">
        <label for="story">Full Story</label>
        <textarea id="story" name="story" rows="7" placeholder="Explain the background, why funds are needed, and how they'll be used."><?php echo h($old['story']); ?></textarea>
        <span class="error-msg"><?php echo $errors['story'] ?? ''; ?></span>
      </div>

      <div class="form-row">
        <div class="field <?php echo isset($errors['goal_amount']) ? 'has-error' : ''; ?>" data-field="goal_amount">
          <label for="goal_amount">Goal Amount (₹)</label>
          <input type="number" id="goal_amount" name="goal_amount" min="1000" value="<?php echo h($old['goal_amount']); ?>" placeholder="e.g. 200000">
          <span class="error-msg"><?php echo $errors['goal_amount'] ?? ''; ?></span>
        </div>
        <div class="field <?php echo isset($errors['deadline']) ? 'has-error' : ''; ?>" data-field="deadline">
          <label for="deadline">Deadline</label>
          <input type="date" id="deadline" name="deadline" value="<?php echo h($old['deadline']); ?>">
          <span class="error-msg"><?php echo $errors['deadline'] ?? ''; ?></span>
        </div>
      </div>

      <?php if ($campaign['status'] === 'Rejected'): ?>
        <div class="resubmit-note">
          This campaign was rejected. Save it as-is to keep the current status, or check the box below to send it back for review.
          <label style="display:flex; align-items:center; gap:8px; margin-top:10px; cursor:pointer;">
            <input type="checkbox" name="resubmit" value="1"> Resubmit for review
          </label>
        </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
    </form>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/validation.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var dropZone = document.getElementById('uploadDrop');
  var fileInput = document.getElementById('cover_image');
  var preview = document.getElementById('uploadPreview');
  var placeholder = document.getElementById('uploadPlaceholder');

  dropZone.addEventListener('click', function () { fileInput.click(); });

  function showPreview(file) {
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  fileInput.addEventListener('change', function () { showPreview(fileInput.files[0]); });

  ['dragover', 'dragleave', 'drop'].forEach(function (evt) {
    dropZone.addEventListener(evt, function (e) { e.preventDefault(); });
  });
  dropZone.addEventListener('dragover', function () { dropZone.classList.add('drag-over'); });
  dropZone.addEventListener('dragleave', function () { dropZone.classList.remove('drag-over'); });
  dropZone.addEventListener('drop', function (e) {
    dropZone.classList.remove('drag-over');
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      showPreview(e.dataTransfer.files[0]);
    }
  });
});
</script>
