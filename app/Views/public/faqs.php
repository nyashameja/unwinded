
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Frequently Asked Questions</h1>
    <p>Everything you need to know about Unwinded experiences.</p>
  </div>
</div>

<section class="section">
  <div class="container container--narrow">

    <?php if ($groups): ?>
      <?php foreach ($groups as $group): ?>
        <?php $gfaqs = $faqsByGroup[$group['id']] ?? []; ?>
        <?php if (!$gfaqs) continue; ?>
        <h2 class="faq-group__heading"><?= e($group['name']) ?></h2>
        <div class="faq-list">
          <?php foreach ($gfaqs as $faq): ?>
          <details class="faq-item">
            <summary class="faq-item__question"><?= e($faq['question']) ?></summary>
            <div class="faq-item__answer prose"><?= nl2br(e($faq['answer'])) ?></div>
          </details>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($ungrouped): ?>
      <?php if ($groups): ?><h2 class="faq-group__heading">Other Questions</h2><?php endif; ?>
      <div class="faq-list">
        <?php foreach ($ungrouped as $faq): ?>
        <details class="faq-item">
          <summary class="faq-item__question"><?= e($faq['question']) ?></summary>
          <div class="faq-item__answer prose"><?= nl2br(e($faq['answer'])) ?></div>
        </details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$groups && !$ungrouped): ?>
      <p class="empty-state">No FAQs yet. <a href="<?= url('/contact') ?>">Get in touch</a> with your question.</p>
    <?php endif; ?>

    <div class="faq-cta">
      <h3>Still have a question?</h3>
      <p>We're happy to help.</p>
      <a href="<?= url('/contact') ?>" class="btn btn--secondary">Contact us</a>
    </div>

  </div>
</section>
