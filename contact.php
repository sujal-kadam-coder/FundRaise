<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

$errors = [];
$success = false;
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $default) { $old[$key] = trim($_POST[$key] ?? $default); }

    if ($old['name'] === '' || strlen($old['name']) < 3) {
        $errors['name'] = 'Please enter your full name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($old['subject'] === '') {
        $errors['subject'] = 'Please select a subject.';
    }
    if ($old['message'] === '' || strlen($old['message']) < 10) {
        $errors['message'] = 'Please write at least a sentence or two.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $old['name'], $old['email'], $old['subject'], $old['message']);
        if ($stmt->execute()) {
            $success = true;
            $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
        } else {
            $errors['general'] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Contact Us';
$active = 'contact';
$basePath = '';
include 'includes/header.php';
?>

<section>
  <div class="section-head center">
    <span class="eyebrow">We're Here to Help</span>
    <h2>Get in Touch</h2>
    <p>Questions about a campaign, a donation, or the platform itself — send us a message.</p>
  </div>

  <div class="split" style="align-items:flex-start;">
    <div>
      <div class="form-card">
        <?php if ($success): ?>
          <div class="alert alert-success">✅ Thanks for reaching out — we'll get back to you within 1–2 business days.</div>
        <?php endif; ?>
        <?php if (!empty($errors['general'])): ?>
          <div class="alert alert-error"><?php echo h($errors['general']); ?></div>
        <?php endif; ?>

        <form method="POST" action="contact.php" novalidate>
          <div class="form-row">
            <div class="field <?php echo isset($errors['name']) ? 'has-error' : ''; ?>" data-field="name">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" value="<?php echo h($old['name']); ?>">
              <span class="error-msg"><?php echo $errors['name'] ?? ''; ?></span>
            </div>
            <div class="field <?php echo isset($errors['email']) ? 'has-error' : ''; ?>" data-field="email">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" value="<?php echo h($old['email']); ?>">
              <span class="error-msg"><?php echo $errors['email'] ?? ''; ?></span>
            </div>
          </div>
          <div class="field <?php echo isset($errors['subject']) ? 'has-error' : ''; ?>" data-field="subject">
            <label for="subject">Subject</label>
            <select id="subject" name="subject">
              <option value="" disabled <?php echo $old['subject'] === '' ? 'selected' : ''; ?>>Choose a topic</option>
              <?php foreach (['Question about a campaign', 'Donation issue', 'Report a campaign', 'Partnership inquiry', 'Something else'] as $s): ?>
                <option value="<?php echo h($s); ?>" <?php echo $old['subject'] === $s ? 'selected' : ''; ?>><?php echo h($s); ?></option>
              <?php endforeach; ?>
            </select>
            <span class="error-msg"><?php echo $errors['subject'] ?? ''; ?></span>
          </div>
          <div class="field <?php echo isset($errors['message']) ? 'has-error' : ''; ?>" data-field="message">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5"><?php echo h($old['message']); ?></textarea>
            <span class="error-msg"><?php echo $errors['message'] ?? ''; ?></span>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Send Message</button>
        </form>
      </div>
    </div>

    <div>
      <div class="contact-info-card">
        <h3>Other Ways to Reach Us</h3>
        <div class="contact-row"><span class="contact-icon">📧</span><div><b>Email</b><br>support@fundraise.example</div></div>
        <div class="contact-row"><span class="contact-icon">📞</span><div><b>Phone</b><br>+91 98765 43210</div></div>
        <div class="contact-row"><span class="contact-icon">📍</span><div><b>Office</b><br>Mumbai, Maharashtra, India</div></div>
        <div class="contact-row"><span class="contact-icon">⏰</span><div><b>Hours</b><br>Mon–Fri, 10 AM – 6 PM IST</div></div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
