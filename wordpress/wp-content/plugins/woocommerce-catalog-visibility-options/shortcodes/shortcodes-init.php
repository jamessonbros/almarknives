<?php

function wc_cvo_login_url($atts, $content = '') {
    if (is_null($content) || empty($content)) {
        $content = __('Login');
    }

    return '<a href="' . wp_login_url() . '">' . $content . '</a>';
}

function wc_cvo_register_url($atts, $content = '') {
    if (is_null($content) || empty($content)) {
        $content = __('Register');
    }
    $url = site_url('wp-login.php?action=register', 'login');
    return '<a href="' . $url . '">' . $content . '</a>';
}

function wc_cvo_forgot_password_link($atts, $content = '') {
    if (is_null($content) || empty($content)) {
        $content = __('Forgot Your Password');
    }

    return '<a href="' . wp_login_url(get_permalink()) . '&action=lostpassword' . '>">' . $content . '</a>';
}

function wc_cvo_logon_form($atts, $content = '') {
    global $error;

    $args = shortcode_atts(array($atts), array());
    $args['echo'] = false;

    $html = '';
    if (isset($_GET['logon']) && $_GET['logon'] == 'failed') {
        $html = '<div class="logon-failed">' . __('Logon Failed') . '</div>';
    }

    $args['redirect_to'] = '';
    $html .= wcvo_login_fields($args);
    $html .= '<script type="text/javascript">jQuery(document).ready(function($) { $("form.cart").attr("action", ""); $("#wp-submit").click(function(event) {  event.preventDefault();  $("form.cart").attr("action", $("#loginform").val() ); $("form.cart").submit();  return false; }); });</script>';
    return $html;
}

function wcvo_login_fields($args = array()) {
    $defaults = array('echo' => true,
        'redirect' => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], // Default redirect is back to the current page
        'form_id' => 'loginform',
        'label_username' => __('Username'),
        'label_password' => __('Password'),
        'label_remember' => __('Remember Me'),
        'label_log_in' => __('Log In'),
        'id_username' => 'user_login',
        'id_password' => 'user_pass',
        'id_remember' => 'rememberme',
        'id_submit' => 'wp-submit',
        'remember' => true,
        'value_username' => '',
        'value_remember' => false, // Set this to true to default the "Remember me" checkbox to checked
    );
    $args = wp_parse_args($args, apply_filters('login_form_defaults', $defaults));

    $form = '
		<input type="hidden" name="' . $args['form_id'] . '" id="' . $args['form_id'] . '" value="' . esc_url(site_url('wp-login.php', 'login_post')) . '" />
			' . apply_filters('login_form_top', '', $args) . '
			<p class="login-username">
				<label for="' . esc_attr($args['id_username']) . '">' . esc_html($args['label_username']) . '</label>
				<input type="text" name="log" id="' . esc_attr($args['id_username']) . '" class="input" value="' . esc_attr($args['value_username']) . '" size="20" />
			</p>
			<p class="login-password">
				<label for="' . esc_attr($args['id_password']) . '">' . esc_html($args['label_password']) . '</label>
				<input type="password" name="pwd" id="' . esc_attr($args['id_password']) . '" class="input" value="" size="20" />
			</p>
			' . apply_filters('login_form_middle', '', $args) . '
			' . ( $args['remember'] ? '<p class="login-remember"><label><input name="rememberme" type="checkbox" id="' . esc_attr($args['id_remember']) . '" value="forever"' . ( $args['value_remember'] ? ' checked="checked"' : '' ) . ' /> ' . esc_html($args['label_remember']) . '</label></p>' : '' ) . '
			<p class="login-submit">
				<input type="submit" name="wp-submit" id="' . esc_attr($args['id_submit']) . '" class="button-primary" value="' . esc_attr($args['label_log_in']) . '" />
				<input type="hidden" name="redirect_to" value="' . esc_url($args['redirect']) . '" />
			</p>
			' . apply_filters('login_form_bottom', '', $args) . '';

    return $form;
}

add_shortcode('woocommerce_logon_link', 'wc_cvo_login_url');
add_shortcode('woocommerce_register_link', 'wc_cvo_register_url');
add_shortcode('woocommerce_forgot_password_link', 'wc_cvo_forgot_password_link');
add_shortcode('woocommerce_logon_form', 'wc_cvo_logon_form');