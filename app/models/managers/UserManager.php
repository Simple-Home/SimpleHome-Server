<?php
class UserManager
{
	public static function getUsers ($filtr = ['*']) {
		try {
			$allUsers = Db::loadAll ("SELECT " . implode(",", $filtr) . " FROM users");
			return $allUsers;
		} catch(PDOException $error) {
			echo $error->getMessage();
			die();
		}
	}

	public static function getUser ($userName) {
		try {
			$user = Db::loadOne ("SELECT * FROM users WHERE username = ?", [$userName]);
			return $user;
		} catch(PDOException $error) {
			echo $error->getMessage();
			die();
		}
	}

	public static function getUserId ($userId) {
		try {
			$user = Db::loadOne ("SELECT * FROM users WHERE user_id = ?", [$userId]);
			return $user;
		} catch(PDOException $error) {
			echo $error->getMessage();
			die();
		}
	}

	public static function getAvatarUrl($userId = null){
		if ($userId == null) {
			$email = self::getUserData('email');
		}
		else if ($userId != null){
			$email = self::getUserData('email',$userId);
		}
		return 'https://secure.gravatar.com/avatar/' . md5( strtolower( trim( $email ) ) );
	}

	public static function login ($username, $password, $rememberMe) {
		try {
			if ($user = Db::loadOne ('SELECT * FROM users WHERE LOWER(username)=LOWER(?)', array ($username))) {
				if ($user['password'] == UserManager::getHashPassword($password)) {
					if (isset($rememberMe) && $rememberMe == 'true') {
						setcookie ("rememberMe", self::setEncryptedCookie($user['username']), time () + (30 * 24 * 60 * 60 * 1000), BASEDIR, $_SERVER['HTTP_HOST'], 1);
					}
					$_SESSION['user']['id'] = $user['user_id'];
					$page = "";
					if ($user["startPage"] == 1) {
						$page = "dashboard";
					}
					unset($_POST['login']);
					return $page;
				} else {
					throw new PDOException("Heslo není správné!");
				}
			} else {
				throw new PDOException("Uživatel s tím to jménem neexistuje!");
			}
		} catch(PDOException $error) {
			echo $error->getMessage();
			die();
		}
	}

	public static function loginNew ($username, $password) {
		try {
			if ($user = Db::loadOne ('SELECT * FROM users WHERE LOWER(username)=LOWER(?)', array ($username))) {
				if ($user['password'] == UserManager::getHashPassword($password)) {
					return $user['user_id'];
				} else {
					return false;
				}
			} else {
				return false;
			}
		} catch(PDOException $error) {
			echo $error->getMessage();
			die();
		}
	}

	public static function isLogin () {
		if (isset ($_SESSION['user']) && isset($_SESSION['user']['id'])) {
			return true;
		} else {
			if (isset ($_COOKIE['rememberMe'])){
				if ($user = Db::loadOne ('SELECT * FROM users WHERE LOWER(username)=LOWER(?)', array (self::getDecryptedCookie($_COOKIE['rememberMe'])))) {
					$_SESSION['user']['id'] = $user['user_id'];
					return true;
				}
			}
		}
		return false;
	}

	public static function logout () {
		unset($_SESSION['user']);
		session_destroy();
		if (isset($_COOKIE['rememberMe'])){
			unset($_COOKIE['rememberMe']);
			setcookie("rememberMe", 'false', time(), BASEDIR, $_SERVER['HTTP_HOST']);
		}
	}

	public static function setEncryptedCookie($value){
		$first_key = base64_decode(FIRSTKEY);
		$second_key = base64_decode(SECONDKEY);

		$method = "aes-256-cbc";
		$ivlen = openssl_cipher_iv_length($method);
		$iv = openssl_random_pseudo_bytes($ivlen);
		$newvalue_raw = openssl_encrypt($value, $method, $first_key, OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $newvalue_raw, $second_key, TRUE);
		$newvalue = base64_encode ($iv.$hmac.$newvalue_raw);
		return $newvalue;
	}

	public static function getDecryptedCookie($value){
		$first_key = base64_decode(FIRSTKEY);
		$second_key = base64_decode(SECONDKEY);

		$c = base64_decode($value);
		$method = "aes-256-cbc";
		$ivlen = openssl_cipher_iv_length($method);
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, 32);
		$newValue_raw = substr($c, $ivlen+32);
		$newValue = openssl_decrypt($newValue_raw, $method, $first_key, OPENSSL_RAW_DATA, $iv);
		$calcmac = hash_hmac('sha256', $newValue_raw, $second_key, TRUE);
		if (hash_equals ($hmac, $calcmac)) {
			return $newValue;
		}
		return false;
	}

	public static function getUserData ($type, $userId = '') {
		if ($userId == '') {
			$userId = $_SESSION['user']['id'];
		}
		$user = Db::loadOne ('SELECT ' . $type . ' FROM users WHERE user_id=?', array ($userId));
		return $user[$type];
	}

	public static function setUserData ($type, $value) {
		if (isset ($_SESSION['user']['id'])) {
			Db::command ('UPDATE users SET ' . $type . '=? WHERE user_id=?', array ($value, $_SESSION['user']['id']));
		}
	}

	public static function getHashPassword ($password) {
		$salt = "s0mRIdlKvI";
		$hashPassword = hash('sha512', ($password . $salt));
		return $hashPassword;
	}

	public static function atHome($userId, $atHome){
		try {
			Db::edit ('users', ['at_home' => $atHome], 'WHERE user_id = ?', array($userId));
		} catch(PDOException $error) {
			echo $error->getMessage();
			die();
		}
	}

	public static function changePassword($oldPassword, $newPassword, $newPassword2){
		if ($newPassword == $newPassword2) {
			//Password Criteria
			$oldPasswordSaved = self::getUserData('password');
			if (self::getHashPassword($oldPassword) == $oldPasswordSaved) {
				self::setUserData('password', self::getHashPassword($newPassword));
			} else {
				throw new Exception ("old password did not match");
			}
		} else {
			throw new Exception ("new password arent same");
		}
	}

	public static function createUser($userName, $password){
		$userId = Db::loadOne('SELECT * FROM users WHERE username = ?;', array($userName))['user_id'];
		if ($userId != null) {
			return false;
		};
		try {
			$user = [
				'username' => $userName,
				'password' => self::getHashPassword($password),
			];
			return Db::add ('users', $user);
		} catch(PDOException $error) {
			echo $error->getMessage();
			die();
		}
	}

	public static function	haveOtaEnabled($userName){
		$ota = self::getUser($userName)['ota'];

		if ($ota != ''){
			return ($ota != '' ? $ota : false);
		} else {
			return false;
		}
	}

	public static function setOta($otaCode, $otaSecret){
		$ga = new PHPGangsta_GoogleAuthenticator();
		$checkResult = $ga->verifyCode($otaSecret, $otaCode, 2);    // 2 = 2*30sec clock tolerance
		if ($checkResult) {
			self::setUserData('ota', $otaSecret);
			return true;
		}
		return false;
	}
}
?>
