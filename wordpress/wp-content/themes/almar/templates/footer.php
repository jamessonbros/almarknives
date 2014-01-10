<div class="red">
  <div class="container">
    <div class="row text-center">
      <h3>At Al Mar Knives, A Warrior's Edge &trade; means Factory Perfection &trade;.</h3>
    </div>
  </div>
</div>

<div class="l-inverse">
  <footer class="content-info container" role="contentinfo">
    <div class="row">
      <?php dynamic_sidebar('sidebar-footer'); ?>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <div class="footer-nav-wrap" id="fmenu">
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
        <p>Site by <a href="http://groovywebdesign.com/"><img src="/media/groovywebdesign.png" width="40"></a></p>
      </div>
    </div>
  </footer>
</div>

<?php wp_footer(); ?>
