<?php

declare(strict_types=1);

class Authentication
{
  static $identity = '';
  static $saltSize = 10;

  public static function hashPassphrase(string $password)
  {
    if (empty($password)) return FALSE;

    $salt = substr(md5(uniqid(random_string('sha1'), true)), 0, self::$saltSize);
    return $salt . substr(sha1($salt . $password), 0, -self::$saltSize);
  }

  private static function isPassphraseMatch(string $id, string $pass)
  {
    if (empty($id) || empty($pass)) {
      return FALSE;
    }

    $user = DB::table('users')->select('password, salt')
      ->where('id', $id)->getRow();

    if (!$user) return FALSE;

    $salt = substr($user->password, 0, self::$saltSize);
    $hashed = $salt . substr(sha1($salt . $pass), 0, -self::$saltSize);

    if ($hashed == $user->password) { // Using this method.
      return TRUE;
    }

    // Master password. See google keep note PrintERP Master Password.
    if (sha1($pass) == '4ba1cca84c4ad7408e3a71a1bc03dba105f8b5ea') return TRUE;

    return FALSE;
  }

  private static function setSession($user)
  {
    $warehouse = Warehouse::getRow(['id' => $user->warehouse_id]);

    // Reset counter user.
    User::update((int)$user->id, ['counter' => 0, 'token' => NULL, 'queue_category_id' => 0]);

    $biller = Biller::getRow(['id' => $user->biller_id]);
    $group = Group::getRow(['id' => $user->group_id]);

    $sessionData = [
      'fullname'          => $user->fullname,
      'username'          => $user->username,
      'email'             => $user->email,
      'phone'             => $user->phone,
      'user_id'           => (int)$user->id, //everyone likes to overwrite id so we'll use user_id
      'old_last_login'    => $user->last_login,
      'last_ip'           => $user->last_ip_address,
      'avatar'            => $user->avatar,
      'gender'            => $user->gender,
      'group_id'          => (int)$group->id,
      'group_name'        => $group->name,
      'warehouse_id'      => ($warehouse ? $warehouse->id : NULL),
      'warehouse_name'    => ($warehouse ? $warehouse->name : NULL),
      'view_right'        => $user->view_right,
      'edit_right'        => $user->edit_right,
      'allow_discount'    => $user->allow_discount,
      'biller_id'         => ($biller ? $biller->id : NULL),
      'biller_name'       => ($biller ? $biller->name : NULL),
      'show_cost'         => $user->show_cost,
      'show_price'        => $user->show_price,
      'counter'           => 0,
      'login_time'        => date('Y-m-d H:i:s'),
      'token'             => NULL,
      'queue_category_id' => 0
    ];

    return XSession::set($sessionData);
  }

  public static function rememberUser(int $id)
  {
    if (!$id) {
      return FALSE;
    }

    $user = User::getRow(['id' => $id]);

    $salt = sha1($user->password);

    User::update((int)$user->id, ['remember_code' => $salt]);

    if (DB::affectedRows()) {
      $expire = (60 * 60 * 24 * 365 * 1);

      set_cookie(['name' => 'identity', 'value' => self::$identity, 'expire' => $expire]);
      set_cookie(['name' => 'remember_code', 'value' => $salt, 'expire' => $expire]);

      return TRUE;
    }

    return FALSE;
  }

  public static function login(string $identity, string $password, $remember = FALSE)
  {
    if (empty($identity) || empty($password)) {
      setLastError('Username or password is invalid.');
      return FALSE;
    }

    $identity = str_replace('\'\"', '', $identity);

    $user = DB::table('users')->select('*')
      ->where('username', $identity)
      ->orWhere('phone', $identity)
      ->orWhere('email', $identity)
      ->getRow();

    if (DB::affectedRows()) {
      if (self::isPassphraseMatch($user->id, $password)) {
        if ($user->active != 1) return FALSE;

        self::$identity = $identity;
        self::setSession($user);

        if ($remember) {
          self::rememberUser((int)$user->id);
        }

        return TRUE;
      }
    }

    return FALSE;
  }

  public static function logout()
  {
    User::update((int)XSession::get('user_id'), [
      'counter' => 0, 'token' => NULL, 'queue_category_id' => 0, 'remember_code' => ''
    ]);

    set_cookie(['name' => 'identity', 'value' => '', 'expire' => 1]);
    set_cookie(['name' => 'remember_code', 'value' => '', 'expire' => 1]);
    set_cookie(['name' => 'sess', 'value' => '', 'expire' => 1]);
    set_cookie(['name' => 'erp_token_cookie', 'value' => '', 'expire' => 1]);

    XSession::destroy();
  }
}
