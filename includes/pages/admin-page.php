<?php
if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}
?>
<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

  <form method="post" action="options.php">
    <?php
    settings_fields('better_fontawesome');
    do_settings_sections('better-fontawesome');
    submit_button('Save Settings');
    ?>
  </form>

</div>