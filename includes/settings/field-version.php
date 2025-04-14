<?php

if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

$nameAttribute = self::OPTION_NAME . "[version]";
$options = get_option(self::OPTION_NAME);
$current_version = isset($options['version']) ? $options['version'] : $this->fontawesome_version;

echo "<select name=\"$nameAttribute\">";

foreach ($this->available_versions as $version => $data) {
  $selected = ($version === $current_version) ? 'selected' : '';
  echo '<option value="' . $version . '" ' . $selected . '>' . $data['label'] . '</option>';
}

echo '</select>';
