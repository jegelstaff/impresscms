<?php
/**
 * Manage users
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Member
 * @subpackage	User
 * @version		SVN: $Id: Handler.php 12074 2012-10-18 18:13:03Z skenow $
 */

defined('ICMS_ROOT_PATH') or exit();

/**
 * User handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of user class objects.
 *
 * @author		Kazumi Ono <onokazu@xoops.org>
 * @category	ICMS
 * @package		Member
 * @subpackage	User
 */
class icms_member_user_Handler 
    extends icms_ipf_Handler {
    
        public function __construct(&$db, $module = 'icms') {
            if (!$module)
                $module = 'icms_member';
            $objName = ($module == 'icms')?'member_user':'user';
            parent::__construct($db, $objName, 'uid', 'uname', 'email', $module, 'users');
        }

	/**
	 * delete a user from the database
	 *
	 * @param object $user reference to the user to delete
	 * @param bool $force
	 * @return bool FALSE if failed.
	 * @TODO we need some kind of error message instead of just a FALSE return to inform whether user was deleted aswell as PM messages.
	 */
	public function delete(&$user, $force = FALSE) {
                if (!($user instanceof icms_member_user_Object))
                    return;
		$sql = sprintf(
			"UPDATE %s SET level = '-1', pass = '%s' WHERE uid = '%u'",
			$this->table, 
                        substr(md5(time()), 0, 8), 
                        (int) $user->getVar('uid')
		);
		if (FALSE != $force) {
			$result = $this->db->queryF($sql);
		} else {
			$result = $this->db->query($sql);
		}
                return (bool)$result;
	}

	/**
	 * delete users matching a set of conditions
	 *
	 * @param object $criteria {@link icms_db_criteria_Element}
	 * @return bool FALSE if deletion failed
	 * @TODO we need to also delete the private messages of the user when we delete them! how do we determine which users were deleted from the criteria????
	 */
	public function deleteAll($criteria = NULL) {
		$sql = sprintf("UPDATE %s SET level= '-1', pass = %s", $this->db->prefix('users'), substr(md5(time()), 0, 8));
		if ($criteria instanceof icms_db_criteria_Element)
                    $sql .= ' ' . $criteria->renderWhere();
                return (bool)$this->db->query($sql);
	}

	/**
	 *  Validates username, email address and password entries during registration
	 *  Username is validated for uniqueness and length
	 *  password is validated for length and strictness
	 *  email is validated as a proper email address pattern
	 *
	 *  @param string $uname User display name entered by the user
	 *  @param string $login_name Username entered by the user
	 *  @param string $email Email address entered by the user
	 *  @param string $pass Password entered by the user
	 *  @param string $vpass Password verification entered by the user
	 *  @param int $uid user id (only applicable if the user already exists)
	 *  @global array $icmsConfigUser user configuration
	 *  @return string of errors encountered while validating the user information, will be blank if successful
	 */
	public function userCheck($login_name, $uname, $email, $pass, $uid = 0) { //$vpass
		global $icmsConfigUser;

		// initializations
		$member_handler = icms::handler('icms_member');
		$thisUser = ($uid > 0) ? $thisUser = $member_handler->getUser($uid) : FALSE;
		$icmsStopSpammers = new icms_core_StopSpammer();
		$stop = '';
		switch ($icmsConfigUser['uname_test_level']) {
			case 0: // strict
				$restriction = '/[^a-zA-Z0-9\_\-]/';
				break;
			case 1: // medium
				$restriction = '/[^a-zA-Z0-9\_\-\<\>\,\.\$\%\#\@\!\\\'\"]/';
				break;
			case 2: // loose
				$restriction = '/[\000-\040]/';
				break;
		}

		// check email
		if ((is_object($thisUser) && $thisUser->getVar('email', 'e') != $email && $email !== FALSE) || !is_object($thisUser)) {
			if (!icms_core_DataFilter::checkVar($email, 'email', 0, 1)) $stop .= _US_INVALIDMAIL . '<br />';
			$count = $this->getCount(icms_buildCriteria(array('email' => addslashes($email))));
			if ($count > 0) $stop .= _US_EMAILTAKEN . '<br />';
		}

		// check login_name
		$login_name = icms_core_DataFilter::icms_trim($login_name);
		if ((is_object($thisUser) && $thisUser->getVar('login_name', 'e') != $login_name && $login_name !== FALSE) || !is_object($thisUser)) {
			if (empty($login_name) || preg_match($restriction, $login_name)) $stop .= _US_INVALIDNICKNAME . '<br />';
			if (strlen($login_name) > $icmsConfigUser['maxuname']) $stop .= sprintf(_US_NICKNAMETOOLONG, $icmsConfigUser['maxuname']) . '<br />';
			if (strlen($login_name) < $icmsConfigUser['minuname']) $stop .= sprintf(_US_NICKNAMETOOSHORT, $icmsConfigUser['minuname']) . '<br />';
			foreach ($icmsConfigUser['bad_unames'] as $bu) {
				if (!empty($bu) && preg_match('/' . $bu . '/i', $login_name)) {
					$stop .= _US_NAMERESERVED . '<br />';
					break;
				}
			}
			if (strrpos($login_name, ' ') > 0) $stop .= _US_NICKNAMENOSPACES . '<br />';
			$count = $this->getCount(icms_buildCriteria(array('login_name' => addslashes($login_name))));
			if ($count > 0) $stop .= _US_LOGINNAMETAKEN . '<br />';
		}

		// check uname
		if ((is_object($thisUser) && $thisUser->getVar('uname', 'e') != $uname && $uname !== FALSE) || !is_object($thisUser)) {
			$count = $this->getCount(icms_buildCriteria(array('uname' => addslashes($uname))));
			if ($count > 0) $stop .= _US_NICKNAMETAKEN . '<br />';
		}

		// check password
		if ($pass !== FALSE) {
			// if (!isset($pass) || $pass == '' || !isset($vpass) || $vpass == '') $stop .= _US_ENTERPWD . '<br />';
			// if ((isset($pass)) && ($pass != $vpass)) {
				// $stop .= _US_PASSNOTSAME . '<br />';
			// } elseif (($pass != '') && (strlen($pass) < $icmsConfigUser['minpass'])) {
			if (($pass != '') && (strlen($pass) < $icmsConfigUser['minpass'])) { 
				$stop .= sprintf(_US_PWDTOOSHORT,$icmsConfigUser['minpass']) . '<br />';
			}
			if (isset($pass) && isset($login_name) && ($pass == $login_name || $pass == icms_core_DataFilter::utf8_strrev($login_name, TRUE) || strripos($pass, $login_name) === TRUE)) $stop .= _US_BADPWD . '<br />';
		}

		// check other things
		if ($icmsStopSpammers->badIP($_SERVER['REMOTE_ADDR'])) $stop .= _US_INVALIDIP . '<br />';

		return $stop;
	}

	/**
	 * Return a linked username or full name for a specific $userid
	 *
	 * @param	integer	$uid	uid of the related user
	 * @param	boolean	$name	TRUE to return the fullname, FALSE to use the username; if TRUE and the user does not have fullname, username will be used instead
	 * @param	array	$users	array already containing icms_member_user_Object objects in which case we will save a query
	 * @param	boolean	$withContact TRUE if we want contact details to be added in the value returned (PM and email links)
	 * @param	boolean	$isAuthor	Set this to TRUE if you want the rel='author' attribute added to the link
	 */
	static public function getUserLink($uid, $name = FALSE, $users = array(), $withContact = FALSE, $isAuthor = FALSE) {
		global $icmsConfig;

		if (!is_numeric($uid)) return $uid;
		$uid = (int) $uid;
		if ($uid > 0) {
			if ($users == array()) {
				$member_handler = icms::handler("icms_member");
				$user = $member_handler->getUser($uid);
			} else {
				if (!isset($users[$uid])) return $icmsConfig["anonymous"];
				$user = $users[$uid];
			}

			if (is_object($user)) {
				$author = $isAuthor ? " rel='author'" : "";
				$fullname = '';
				$linkeduser = '';

				$username = $user->getVar('uname');
				$fullname2 = $user->getVar('name');
				if (($name) && !empty($fullname2)) $fullname = $user->getVar('name');
				if (!empty($fullname)) $linkeduser = $fullname . "[";
                $linkeduser .= '<a href="' . ICMS_URL . '/userinfo.php?uid=' . $uid . '"' . $author . '>';
				$linkeduser .= icms_core_DataFilter::htmlSpecialChars($username) . "</a>";
				if (!empty($fullname)) $linkeduser .= "]";

				if ($withContact) {
					$linkeduser .= '<a href="mailto:' . $user->getVar('email') . '">';
					$linkeduser .= '<img style="vertical-align: middle;" src="' . ICMS_IMAGES_URL
						. '/icons/' . $icmsConfig["language"] . '/email.gif' . '" alt="'
						. _US_SEND_MAIL . '" title="' . _US_SEND_MAIL . '"/></a>';
					$js = "javascript:openWithSelfMain('" . ICMS_URL . '/pmlite.php?send2=1&to_userid='
						. $uid . "', 'pmlite', 450, 370);";
					$linkeduser .= '<a href="' . $js . '"><img style="vertical-align: middle;" src="'
						. ICMS_IMAGES_URL . '/icons/' . $icmsConfig["language"] . '/pm.gif'
						. '" alt="' . _US_SEND_PM . '" title="' . _US_SEND_PM . '"/></a>';
				}

				return $linkeduser;
			}
		}
		return $icmsConfig["anonymous"];
	}

	/**
	 * Retrieve a username from the database given an email address
	 *
	 * @param	string	$email Email address for a user
	 * @return	string	A username matching the provided email address
	 */
	static public function getUnameFromEmail($email = '') {
                $handler = icms::handler('icms_member_user');                
		if ($email !== '') {
			$sql = $db->query("SELECT uname, email FROM " . $handler->table
				. " WHERE email = '" . @htmlspecialchars($email, ENT_QUOTES, _CHARSET)
				. "'");
			list($uname, $email) = $db->fetchRow($sql);
		} else {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}
		return $uname;
	}
        
        /**
	 * find the username for a given ID
	 *
	 * @param int $userid ID of the user to find
	 * @param bool $usereal switch for usename or realname
	 * @return string name of the user. name for "anonymous" if not found.
	 */
	public function getUnameFromId($userid, $usereal = false) {
		$userid = (int) $userid;
		if ($userid > 0) {
                        $sql = $this->db->query(
                                'SELECT '.($usereal?'name':'uname').' FROM ' . $handler->table
				. " WHERE userid = '"
                                . $userid
				. "'"
                               );
			list($name) = $this->db->fetchRow($sql);
                        if ($name)
                            return icms_core_DataFilter::htmlSpecialChars($name);			
		}
		return $GLOBALS['icmsConfig']['anonymous'];
	}        
        
	public function getList($criteria = NULL) {
		$users = $this->getObjects($criteria, TRUE);
		$ret = array();
		foreach (array_keys($users) as $i) {
			$ret[$i] = $users[$i]->getVar('uname');
		}
		return $ret;
	}
}
>>>>>>> branches/impresscms_2.0
