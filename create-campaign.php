<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
require_login();

$categories = [];
$res = $conn->query('SELECT id, name, icon FROM categories ORDER BY name');
while ($row = $res->fetch_assoc()) { $categories[] = $row; }

$errors = [];
$old = ['title' => '', 'category_id' => '', 'short_description' => '', 'story' => '', 'goal_amount' => '', 'deadline' => '', 'cover_color' => 'teal'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $default) {
        if ($key !== 'cover_color') $old[$key] = trim($_POST[$key] ?? $default);
    }
    $old['cover_color'] = in_array($_POST['cover_color'] ?? '', ['teal', 'coral', 'amber']) ? $_POST['cover_color'] : 'teal';

    if ($old['title'] === '' || strlen($old['title']) < 10) {
        $errors['title'] = 'Title should be at least 10 characters.';
    }
    $categoryId = (int)$old['category_id'];
    if ($categoryId <= 0) {
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

    // ---- Optional cover image upload ----
    $coverImagePath = null;
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
        $uid = current_user_id();
        $stmt = $conn->prepare(
            "INSERT INTO campaigns (user_id, category_id, title, short_description, story, goal_amount, deadline, cover_color, cover_image, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
        );
        $stmt->bind_param('iisssdsss', $uid, $categoryId, $old['title'], $old['short_description'], $old['story'], $goal, $old['deadline'], $old['cover_color'], $coverImagePath);
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            $stmt->close();
            header('Location: campaign.php?id=' . $newId . '&submitted=1');
            exit;
        } else {
            $errors['general'] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Start a Campaign';
$basePath = '';
include 'includes/header.php';
?>

<section>
  <div class="section-head center">
    <span class="eyebrow">Start Something</span>
    <h2>Tell us about your campaign</h2>
    <p>Every submission is reviewed by our team before it goes public — usually within 24 hours.</p>
    <?php if (current_user_role() === 'Donor'): ?>
      <p style="font-size:0.82rem; color:var(--coral); margin-top:6px;">Note: your account is registered as a Donor. You can still start a campaign — it'll simply appear under your account alongside your donations.</p>
    <?php endif; ?>
  </div>

  <div class="form-card" style="max-width:760px; margin:0 auto;">
    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error"><?php echo h($errors['general']); ?></div>
    <?php endif; ?>

    <form method="POST" action="create-campaign.php" id="campaignForm" enctype="multipart/form-data" novalidate>
      <div class="field <?php echo isset($errors['title']) ? 'has-error' : ''; ?>" data-field="title">
        <label for="title">Campaign Title</label>
        <input type="text" id="title" name="title" value="<?php echo h($old['title']); ?>" placeholder="e.g. Help Rebuild Our Village School">
        <span class="error-msg"><?php echo $errors['title'] ?? ''; ?></span>
      </div>

      <div class="field <?php echo isset($errors['cover_image']) ? 'has-error' : ''; ?>" data-field="cover_image">
        <label for="cover_image">Cover Photo <span style="font-weight:400; color:var(--slate);">(optional — JPG/PNG/WEBP, max 5MB)</span></label>
        <div class="upload-drop" id="uploadDrop">
          <img id="uploadPreview" style="display:none;" alt="Preview">
          <div id="uploadPlaceholder">
            <span class="upload-icon">📷</span>
            <span>Click to choose a photo, or drag one here</span>
          </div>
        </div>
        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
        <span class="error-msg"><?php echo $errors['cover_image'] ?? ''; ?></span>
      </div>

      <div class="form-row">
        <div class="field <?php echo isset($errors['category_id']) ? 'has-error' : ''; ?>" data-field="category_id">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id">
            <option value="" disabled <?php echo $old['category_id'] === '' ? 'selected' : ''; ?>>Choose a category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo (string)$old['category_id'] === (string)$cat['id'] ? 'selected' : ''; ?>><?php echo $cat['icon']; ?> <?php echo h($cat['name']); ?></option>
            <?php endforeach; ?>
          </select>
          <span class="error-msg"><?php echo $errors['category_id'] ?? ''; ?></span>
        </div>
        <div class="field">
          <label for="cover_color">Fallback Color <span style="font-weight:400; color:var(--slate);">(used only if no photo is uploaded)</span></label>
          <select id="cover_color" name="cover_color">
            <option value="teal" <?php echo $old['cover_color'] === 'teal' ? 'selected' : ''; ?>>Teal</option>
            <option value="coral" <?php echo $old['cover_color'] === 'coral' ? 'selected' : ''; ?>>Coral</option>
            <option value="amber" <?php echo $old['cover_color'] === 'amber' ? 'selected' : ''; ?>>Amber</option>
          </select>
        </div>
      </div>

      <div class="field <?php echo isset($errors['short_description']) ? 'has-error' : ''; ?>" data-field="short_description">
        <label for="short_description">Short Summary <span style="font-weight:400; color:var(--slate);">(shown on campaign cards, max 200 characters)</span></label>
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

      <button type="submit" class="btn btn-primary btn-block">Submit for Review</button>
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
