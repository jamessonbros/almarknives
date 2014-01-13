<?php global $post; ?>

<div class="jumbotron jumbotron-product-single l-inverse">
  <div class="container">
    <h1><?php the_title() ?></h1>
    <?php if (has_post_thumbnail()): ?>
    <div class="product-hero-image">
      <?php the_post_thumbnail() ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="container product-content-container">
  <div class="row">
    <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
      <h2>Overview</h2>
    </div>
    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
      <h2 class="product-price-tag">
    </div>
  </div>
</div>