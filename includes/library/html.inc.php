<?php

/**
 * [Admin, Site] HTML builder for option elements.
 */
function html_option_ele($value, $label, $selected = false) {
   $selected_attr = $selected ? 'selected="selected"' : '';
   return "<option value=\"{$value}\" {$selected_attr}>{$label}</option>";
}

/**
 * [Admin, Site] HTML builder for disabled attributes.
 */
function html_disabled_attr($disabled) {
  return $disabled ? 'disabled' : '';
}

?>
