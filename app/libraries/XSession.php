<?php

declare(strict_types=1);

/**
 * Extended version of session class.
 */
class XSession
{
  public static function delete(string $name)
  {
    unset($_SESSION[$name]);
  }

  public static function has(string $name)
  {
    return isset($_SESSION[$name]);
  }

  public static function get(string $name)
  {
    return ($_SESSION[$name] ?? NULL);
  }

  public static function set($name, string $value = '')
  {
    $c = 0;

    if (is_array($name)) {
      foreach ($name as $n => $v) {
        $_SESSION[$n] = $v;
        $c++;
      }
    } else {
      $_SESSION[$name] = $value;
      $c++;
    }

    return $c;
  }
}
