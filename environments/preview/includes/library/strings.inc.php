<?php

/**
 * [Site] Given float, produces a string for price.
 */
function as_price($price) {
  $format = '%.2f';
  if ($price < 0) {
    return '-$'.sprintf($format,abs($price));
  } else {
    return '$'.sprintf($format,$price);
  }
}

/**
 * [Site] Given float, produces a string for percent.
 */
function as_percent($percent) {
  $format = '%.2f';
  if ($percent >= 0) {
      return '+'.sprintf($format, $percent).'%';
  } else {
      return sprintf($format, $percent).'%';
  }
}

/**
 * [Admin] Helper method. Source: http://www.php.net/manual/en/function.sprintf.php#83779
 *
 * Example:
 *   echo sprintf3( 'Hello %your_name%, my name is %my_name%! I am %my_age%, how old are you? I like %object% and I want to %objective_in_life%!' ,
 *   array( 'your_name'         => 'Matt'
 *        , 'my_name'           => 'Jim'
 *        , 'my_age'            => 'old'
 *        , 'object'            => 'women'
 *        , 'objective_in_life' => 'write code'));
 */
function sprintf3($str, $vars, $char = '%') {
    $tmp = array();
    foreach($vars as $k => $v) {
        $tmp[$char . $k . $char] = $v;
    }
    return str_replace(array_keys($tmp), array_values($tmp), $str);
}

/**
 * Source: http://stackoverflow.com/a/834355
 */
function startsWith($haystack, $needle) {
    return !strncmp($haystack, $needle, strlen($needle));
}

?>
