<?php

declare(strict_types=1);

/**
 * Extended version of session class.
 */
class XSession
{
  public static function all()
  {
    return $_SESSION;
  }

  public static function delete(string $name)
  {
    if (isset($_SESSION[$name])) {
      unset($_SESSION[$name]);
    }
  }

  public static function destroy()
  {
    return session_destroy();
  }

  public static function has(string $name)
  {
    return isset($_SESSION[$name]);
  }

  public static function get(string $name)
  {
    if (isset($_SESSION['fd_' . $name])) {
      $data = $_SESSION['fd_' . $name];
      unset($_SESSION['fd_' . $name]);
      return $data;
    }

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

  public static function set_flash($name, string $value = '')
  {
    $c = 0;

    if (is_array($name)) {
      foreach ($name as $n => $v) {
        $_SESSION['fd_' . $n] = $v;
        $c++;
      }
    } else {
      $_SESSION['fd_' . $name] = $value;
      $c++;
    }

    return $c;
  }
}
