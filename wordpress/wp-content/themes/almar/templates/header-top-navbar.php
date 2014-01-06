<div class="stripe">
  <div class="container">
    <div class="row">
      <div class="col-xs-6 col-sm-8 col-md-8">
        <a class="xs-link visible-xs" href="#contentinfo">More info &raquo;</a>
        <nav class="bar-nav-wrap hidden-xs">
        <?php
          if (has_nav_menu('primary_navigation')) :
            wp_nav_menu(array(
              'theme_location' => 'bar_navigation',
              'menu_class' => 'list-inline',
              'menu_id' => 'bar-nav',
              'container' => false,
              'depth' => 1,
              'fallback_cb' => false,
            ));
          endif;
        ?>
        </nav>
      </div>
      <div class="col-xs-6 col-sm-4 col-md-4">
        <div class="questions text-right">
          <a href="/support"><span class="glyphicon glyphicon-question-sign"></span> Questions?</a>
        </div>
      </div>
    </div>
  </div>
</div>

<header class="banner navbar navbar-inverse navbar-static-top" role="banner">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?php echo home_url(); ?>/"><?php bloginfo('name'); ?></a>
    </div>

    <nav class="collapse navbar-collapse" role="navigation">
      <?php
        if (has_nav_menu('primary_navigation')) :
          wp_nav_menu(array('theme_location' => 'primary_navigation', 'menu_class' => 'nav navbar-nav'));
        endif;
      ?>
    </nav>
  </div>
</header>
