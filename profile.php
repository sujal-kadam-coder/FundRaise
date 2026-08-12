<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
require_login();

$uid = current_user_id();

$stmt = $conn->prepare('SELECT id, full_name, email, phone, role, created_at FROM users WHERE id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { header('Location: logout.php'); exit; }

$errors = [];
$success = [];

$profile = ['full_name' => $user['full_name'], 'phone' => $user['phone']];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'profile') {
    $profile['full_name'] = trim($_POST['full_name'] ?? '');
    $profile['phone'] = trim($_POST['phone'] ?? '');

    if ($profile['full_name'] === '' || strlen($profile['full_name']) < 3) {
        $errors['profile_full_name'] = 'Please enter your full name (min 3 characters).';
    }
    if (!preg_match('/^[6-9]\d{9}$/', $profile['phone'])) {
        $errors['profile_phone'] = 'Enter a valid 10-digit mobile number.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->bind_param('ssi', $profile['full_name'], $profile['phone'], $uid);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $profile['full_name'];
            $user['full_name'] = $profile['full_name'];
            $user['phone'] = $profile['phone'];
            $success['profile'] = 'Your profile has been updated.';
        } else {
            $errors['profile_general'] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

$passwordErrors = [];
$passwordSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()['password'];
    $stmt->close();

    if (!password_verify($current, $hash)) {
        $passwordErrors['current_password'] = 'Your current password is incorrect.';
    }
    if (strlen($new) < 6) {
        $passwordErrors['new_password'] = 'New password must be at least 6 characters.';
    }
    if ($confirm !== $new) {
        $passwordErrors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($passwordErrors)) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $newHash, $uid);
        if ($stmt->execute()) {
            $passwordSuccess = true;
        } else {
            $passwordErrors['general'] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

// ---- Stats for the sidebar ----
$stats = ['campaigns' => 0, 'donations' => 0];
$stmt = $conn->prepare('SELECT COUNT(*) c FROM campaigns WHERE user_id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) { $stats['campaigns'] = $row['c']; }
$stmt->close();
$stmt = $conn->prepare('SELECT COUNT(*) c, COALESCE(SUM(amount),0) total FROM donations WHERE user_id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) { $stats['donations'] = $row['c']; $stats['total'] = $row['total']; }
$stmt->close();

$pageTitle = 'My Profile';
$active = 'dashboard';
$basePath = '';
include 'includes/header.php';
?>

<section>
  <div class="section-head center">
    <span class="role-badge <?php echo strtolower($user['role']); ?>" style="margin-bottom:10px;"><?php echo $user['role'] === 'Creator' ? '🚀 Campaign Creator Account' : '🤲 Donor Account'; ?></span>
    <h2><?php echo h($user['full_name']); ?>'s Profile</h2>
    <p>Update your details and keep your account secure.</p>
  </div>

  <div class="profile-grid">
    <div class="profile-side">
      <div class="profile-avatar"><?php echo h(strtoupper(substr($user['full_name'], 0, 1))); ?></div>
      <div class="profile-name"><?php echo h($user['full_name']); ?></div>
      <div class="profile-email"><?php echo h($user['email']); ?></div>
      <div class="profile-stats">
        <div class="profile-stat"><b><?php echo (int)$stats['campaigns']; ?></b><span>Campaigns</span></div>
        <div class="profile-stat"><b><?php echo (int)$stats['donations']; ?></b><span>Donations</span></div>
        <div class="profile-stat"><b><?php echo isset($stats['total']) ? money($stats['total']) : '₹0'; ?></b><span>Donated</span></div>
      </div>
      <p class="profile-joined">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
    </div>

    <div class="profile-main">
      <div class="form-card">
        <h3 style="margin-bottom:20px;">Profile Details</h3>
        <?php if (!empty($success['profile'])): ?>
          <div class="alert alert-success"><?php echo h($success['profile']); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors['profile_general'])): ?>
          <div class="alert alert-error"><?php echo h($errors['profile_general']); ?></div>
        <?php endif; ?>

        <form method="POST" action="profile.php" id="profileForm" novalidate>
          <input type="hidden" name="form_type" value="profile">
          <div class="field <?php echo isset($errors['profile_full_name']) ? 'has-error' : ''; ?>" data-field="full_name">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo h($profile['full_name']); ?>">
            <span class="error-msg"><?php echo $errors['profile_full_name'] ?? ''; ?></span>
          </div>
          <div class="field">
            <label for="email">Email Address <span style="font-weight:400; color:var(--slate);">(used for login)</span></label>
            <input type="email" id="email" value="<?php echo h($user['email']); ?>" disabled>
          </div>
          <div class="field <?php echo isset($errors['profile_phone']) ? 'has-error' : ''; ?>" data-field="phone">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?php echo h($profile['phone']); ?>" placeholder="10-digit mobile number">
            <span class="error-msg"><?php echo $errors['profile_phone'] ?? ''; ?></span>
          </div>
          <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
      </div>

      <div class="form-card">
        <h3 style="margin-bottom:20px;">Change Password</h3>
        <?php if ($passwordSuccess): ?>
          <div class="alert alert-success">Your password has been changed.</div>
        <?php endif; ?>
        <?php if (!empty($passwordErrors['general'])): ?>
          <div class="alert alert-error"><?php echo h($passwordErrors['general']); ?></div>
        <?php endif; ?>

        <form method="POST" action="profile.php" id="passwordForm" novalidate>
          <input type="hidden" name="form_type" value="password">
          <div class="field <?php echo isset($passwordErrors['current_password']) ? 'has-error' : ''; ?>" data-field="current_password">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password">
            <span class="error-msg"><?php echo $passwordErrors['current_password'] ?? ''; ?></span>
          </div>
          <div class="form-row" style="margin-bottom:0;">
            <div class="field <?php echo isset($passwordErrors['new_password']) ? 'has-error' : ''; ?>" data-field="new_password">
              <label for="new_password">New Password</label>
              <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" autocomplete="new-password">
              <span class="error-msg"><?php echo $passwordErrors['new_password'] ?? ''; ?></span>
            </div>
            <div class="field <?php echo isset($passwordErrors['confirm_password']) ? 'has-error' : ''; ?>" data-field="confirm_password">
              <label for="confirm_password">Confirm New Password</label>
              <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
              <span class="error-msg"><?php echo $passwordErrors['confirm_password'] ?? ''; ?></span>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/validation.js"></script>
