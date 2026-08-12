<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'role' => 'Donor'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['full_name', 'email', 'phone'] as $key) { $old[$key] = trim($_POST[$key] ?? ''); }
    $old['role'] = in_array($_POST['role'] ?? '', ['Donor', 'Creator']) ? $_POST['role'] : 'Donor';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($old['full_name'] === '' || strlen($old['full_name']) < 3) {
        $errors['full_name'] = 'Please enter your full name (min 3 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[6-9]\d{9}$/', $old['phone'])) {
        $errors['phone'] = 'Enter a valid 10-digit mobile number.';
    }
    if (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }
    if ($confirmPassword !== $password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $old['email']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors['email'] = 'An account with this email already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $old['full_name'], $old['email'], $hash, $old['phone'], $old['role']);
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $old['full_name'];
            $_SESSION['user_role'] = $old['role'];
            $stmt->close();
            header('Location: ' . dashboard_url_for_role($old['role']));
            exit;
        } else {
            $errors['general'] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Sign Up';
$basePath = '';
include 'includes/header.php';
?>

<section>
  <div class="section-head center">
    <span class="eyebrow">Join FundRaise</span>
    <h2>Create your account</h2>
    <p>One account lets you donate to campaigns and start your own.</p>
  </div>

  <div class="form-card" style="max-width:500px; margin:0 auto;">
    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error"><?php echo h($errors['general']); ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="registerForm" novalidate>
      <div class="field" data-field="role" style="margin-bottom:22px;">
        <label>I'm joining as a…</label>
        <div class="role-picker">
          <label class="role-option <?php echo $old['role'] === 'Donor' ? 'selected' : ''; ?>">
            <input type="radio" name="role" value="Donor" <?php echo $old['role'] === 'Donor' ? 'checked' : ''; ?>>
            <span class="role-icon">🤲</span>
            <span class="role-label">Donor</span>
            <span class="role-desc">I want to support causes</span>
          </label>
          <label class="role-option <?php echo $old['role'] === 'Creator' ? 'selected' : ''; ?>">
            <input type="radio" name="role" value="Creator" <?php echo $old['role'] === 'Creator' ? 'checked' : ''; ?>>
            <span class="role-icon">🚀</span>
            <span class="role-label">Campaign Creator</span>
            <span class="role-desc">I want to raise funds</span>
          </label>
        </div>
      </div>
      <div class="field <?php echo isset($errors['full_name']) ? 'has-error' : ''; ?>" data-field="full_name">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" value="<?php echo h($old['full_name']); ?>">
        <span class="error-msg"><?php echo $errors['full_name'] ?? ''; ?></span>
      </div>
      <div class="field <?php echo isset($errors['email']) ? 'has-error' : ''; ?>" data-field="email">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?php echo h($old['email']); ?>">
        <span class="error-msg"><?php echo $errors['email'] ?? ''; ?></span>
      </div>
      <div class="field <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>" data-field="phone">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" value="<?php echo h($old['phone']); ?>" placeholder="10-digit mobile number">
        <span class="error-msg"><?php echo $errors['phone'] ?? ''; ?></span>
      </div>
      <div class="field <?php echo isset($errors['password']) ? 'has-error' : ''; ?>" data-field="password">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="At least 6 characters">
        <span class="error-msg"><?php echo $errors['password'] ?? ''; ?></span>
      </div>
      <div class="field <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>" data-field="confirm_password">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password">
        <span class="error-msg"><?php echo $errors['confirm_password'] ?? ''; ?></span>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>
    <p style="text-align:center; font-size:0.86rem; color:var(--slate); margin-top:18px;">Already have an account? <a href="login.php" style="color:var(--teal); font-weight:600;">Log in</a></p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/validation.js"></script>
