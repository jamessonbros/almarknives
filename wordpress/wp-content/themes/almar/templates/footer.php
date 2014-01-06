<footer class="content-info container" role="contentinfo">
  <div class="row">
    <div class="col-lg-12">
      <?php dynamic_sidebar('sidebar-footer'); ?>
      <div class="footer-nav-wrap">
      <?php 
        if (has_nav_menu('footer_navigation')):
          wp_nav_menu(array(
            'theme_location' => 'footer_navigation',
            'depth' => 1,
            'container' => false,
            'menu_class' => 'footer-nav list-inline',
            'fallback_cb' => false,
          ));
        endif;
      ?>
      </div>
      <p>&copy; <?php echo date('Y'); ?> <a href="<?php echo home_url() ?>"><?php bloginfo('name'); ?></a></p>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
