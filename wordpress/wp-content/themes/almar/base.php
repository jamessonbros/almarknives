<?php get_template_part('templates/head'); ?>
<body <?php body_class(); ?>>

  <?php get_template_part('templates/browsehappy') ?>

  <?php
    do_action('get_header');
    get_template_part('templates/header-top-navbar');
  ?>

  <?php do_action('almar_jumbotron') ?>

  <div class="wrap container" role="document">
    <div class="content row">
      <main class="main <?php echo roots_main_class(); ?>" role="main">
        <?php include roots_template_path(); ?>
      </main><!-- /.main -->
      <?php if (roots_display_sidebar()) : ?>
        <aside class="sidebar <?php echo roots_sidebar_class(); ?>" role="complementary">
          <?php include roots_sidebar_path(); ?>
        </aside><!-- /.sidebar -->
      <?php endif; ?>
    </div><!-- /.content -->
  </div><!-- /.wrap -->

  <?php get_template_part('templates/footer'); ?>

</body>
</html>
