<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email = '';
$next = $_GET['next'] ?? $_POST['next'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id, full_name, password, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . ($next !== 'dashboard.php' ? $next : dashboard_url_for_role($user['role'])));
            exit;
        } else {
            $errors['general'] = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Login';
$basePath = '';
include 'includes/header.php';
?>

<section>
  <div class="section-head center">
    <span class="eyebrow">Welcome Back</span>
    <h2>Log in to your account</h2>
  </div>

  <div class="form-card" style="max-width:440px; margin:0 auto;">
    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error"><?php echo h($errors['general']); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm" novalidate>
      <input type="hidden" name="next" value="<?php echo h($next); ?>">
      <div class="field <?php echo isset($errors['email']) ? 'has-error' : ''; ?>" data-field="email">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?php echo h($email); ?>">
        <span class="error-msg"><?php echo $errors['email'] ?? ''; ?></span>
      </div>
      <div class="field <?php echo isset($errors['password']) ? 'has-error' : ''; ?>" data-field="password">
        <label for="password">Password</label>
        <input type="password" id="password" name="password">
        <span class="error-msg"><?php echo $errors['password'] ?? ''; ?></span>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
    <p style="text-align:center; font-size:0.86rem; color:var(--slate); margin-top:18px;">New here? <a href="register.php" style="color:var(--teal); font-weight:600;">Create an account</a></p>
    <p style="text-align:center; font-size:0.78rem; color:var(--slate); margin-top:10px;">Demo login: meera.joshi@example.com / admin123</p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/validation.js"></script>
