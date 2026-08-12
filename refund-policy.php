<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
$pageTitle = 'Refund Policy';
$basePath = '';
include 'includes/header.php';
?>

<section>
  <div class="legal-page">
    <span class="eyebrow">Legal</span>
    <h1>Refund Policy</h1>
    <p class="legal-updated">Last updated: 1 August 2026</p>

    <h2>1. General Policy</h2>
    <p>Donations made through FundRaise are intended as voluntary, final contributions to a campaign's stated cause. As a general rule, donations are non-refundable once processed.</p>

    <h2>2. Exceptions</h2>
    <p>We will consider a refund in the following situations:</p>
    <ul class="legal-list">
      <li>A duplicate donation was made in error (e.g., the same donation submitted twice within a few minutes).</li>
      <li>A campaign is found, after investigation, to have been fraudulent or knowingly misrepresented.</li>
      <li>A technical error on our platform resulted in an incorrect donation amount being recorded.</li>
    </ul>

    <h2>3. How to Request a Refund</h2>
    <p>To request a refund under one of the exceptions above, contact our support team with your donation reference ID (shown on your payment confirmation) within 7 days of the donation.</p>

    <h2>4. Campaign Fund Withdrawals</h2>
    <p>Once funds have been withdrawn by a Creator, refunds for that portion are no longer possible through the platform. Disputes involving withdrawn funds are handled on a case-by-case basis.</p>

    <p class="legal-note">This is a project-document template prepared for an academic UI/UX and web development exercise, and does not constitute a binding refund policy for a live commercial service.</p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
