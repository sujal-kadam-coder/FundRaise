<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';

$pageTitle = 'FAQ';
$active = 'faq';
$basePath = '';
include 'includes/header.php';

$faqs = [
    ['q' => 'How does FundRaise verify campaigns?', 'a' => 'Every campaign is manually reviewed by our team before it appears publicly. We check that the story, goal, and deadline are reasonable, and reserve the right to reject campaigns that do not meet our guidelines.'],
    ['q' => 'Is my donation refundable?', 'a' => "Donations on FundRaise are intended to directly support the campaign creator's cause. As with most crowdfunding platforms, contributions are generally non-refundable once made \xe2\x80\x94 please donate only to causes you are confident in."],
    ['q' => 'What happens if a campaign does not reach its goal?', 'a' => "Unlike some platforms, FundRaise uses \"keep what you raise\" funding \xe2\x80\x94 creators receive whatever amount has been raised by the deadline, even if the full goal is not met."],
    ['q' => 'How do I know funds are being used properly?', 'a' => "Campaign creators can post public updates visible to all donors, and campaigns with a transparency report attached build more trust. We encourage donors to review a creator's update history before giving."],
    ['q' => 'Can I donate anonymously?', 'a' => "Yes. When donating, simply check \"Donate anonymously\" and your name will not be shown publicly on the campaign page \xe2\x80\x94 though it is still recorded in our system for security purposes."],
    ['q' => 'How long does it take for a campaign to get approved?', 'a' => 'Most campaigns are reviewed within 24 hours of submission. You will be able to see the status ("Pending", "Approved", or "Rejected") from your dashboard.'],
    ['q' => 'Is there a fee for starting a campaign?', 'a' => "Starting a campaign is free. This is a student and educational build, so no real payment processing fees apply here \xe2\x80\x94 a production version would typically include a small platform fee."],
    ['q' => 'Can I edit my campaign after it is published?', 'a' => 'You can post updates to your campaign at any time from your Creator dashboard. For major changes to the goal or story, please contact us directly.'],
];
?>

<section style="padding-bottom:30px;">
  <div class="section-head center">
    <span class="eyebrow">Help Center</span>
    <h2>Frequently Asked Questions</h2>
    <p>Can't find what you're looking for? <a href="contact.php" style="color:var(--teal); font-weight:600;">Contact us</a> directly.</p>
  </div>

  <div class="faq-list">
    <?php foreach ($faqs as $i => $faq): ?>
      <div class="faq-item <?php echo $i === 0 ? 'open' : ''; ?>">
        <div class="faq-question">
          <span><?php echo h($faq['q']); ?></span>
          <span class="faq-toggle">+</span>
        </div>
        <div class="faq-answer">
          <p><?php echo h($faq['a']); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
