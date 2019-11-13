<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //

/**
 * @copyright	http://www.XOOPS.org/
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		System
 * @subpackage	Modules
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 */

/* set get and post filters before including admin_header, if not strings */
$filter_get = array('mid' => 'int');

$filter_post = array('mid' => 'int');

/* set default values for variables. $op and $fct are handled in the header */
$mid = 0;

/** common header for the admin functions */
include "admin_header.php";

!empty($op) || $op = 'list';

$icmsAdminTpl = new icms_view_Tpl();

include_once ICMS_MODULES_PATH . "/system/admin/modules/modules.php";
icms_loadLanguageFile('system', 'blocks', true); // @todo - why is this here?

if (in_array($op, array('submit', 'install_ok', 'update_ok', 'uninstall_ok'))) {
	if (!icms::$security->check()) {
		$op = 'list';
	}
}

switch ($op) {
		case "list":
			icms_cp_header();
			echo xoops_module_list();
			icms_cp_footer();
			break;

		case "confirm":
			icms_cp_header();
			$error = array();
			if (!is_writable(ICMS_CACHE_PATH . '/')) {
				// attempt to chmod 666
				if (!chmod(ICMS_CACHE_PATH . '/', 0777)) {
					$error[] = sprintf(_MUSTWABLE, "<strong>" . ICMS_CACHE_PATH . '/</strong>');
				}
			}

			if (count($error) > 0) {
				icms_core_Message::error($error);
				echo "<p><a class='btn btn-primary' href='admin.php?fct=modules'>" . _MD_AM_BTOMADMIN . "</a></p>";
				icms_cp_footer();
				break;
			}

			echo "<h4 style='text-align:" . _GLOBAL_LEFT . ";'>" . _MD_AM_PCMFM . "</h4>"
			. "<form action='admin.php' method='post'>"
			. "<input type='hidden' name='fct' value='modules' />"
			. "<input type='hidden' name='op' value='submit' />"
			. "<table width='100%' border='0' cellspacing='1' class='table outer'>"
			. "<tr class='center' align='center'><th>" . _CO_ICMS_MODULE . "</th><th>" . _AM_ACTION . "</th><th>" . _MD_AM_ORDER . "</th></tr>";
			$mcount = 0;
			foreach ($module as $mid) {
				if ($mcount % 2 != 0) {
					$class = 'odd';
				} else {
					$class = 'even';
				}
				echo '<tr class="' . $class . '"><td align="center">' . icms_core_DataFilter::stripSlashesGPC($oldname[$mid]);
				$newname[$mid] = trim(icms_core_DataFilter::stripslashesGPC($newname[$mid]));
				if ($newname[$mid] != $oldname[$mid]) {
					echo '&nbsp;&raquo;&raquo;&nbsp;<span style="color:#ff0000;font-weight:bold;">' . $newname[$mid] . '</span>';
				}
				echo '</td><td align="center">';
				if (isset($newstatus[$mid]) && $newstatus[$mid] == 1) {
					if ($oldstatus[$mid] == 0) {
						echo "<span style='color:#ff0000;font-weight:bold;'>" . _MD_AM_ACTIVATE . "</span>";
					} else {
						echo _MD_AM_NOCHANGE;
					}
				} else {
					$newstatus[$mid] = 0;
					if ($oldstatus[$mid] == 1) {
						echo "<span style='color:#ff0000;font-weight:bold;'>" . _MD_AM_DEACTIVATE . "</span>";
					} else {
						echo _MD_AM_NOCHANGE;
					}
				}
				echo "</td><td align='center'>";
				if ($oldweight[$mid] != $weight[$mid]) {
					echo "<span style='color:#ff0000;font-weight:bold;'>" . $weight[$mid] . "</span>";
				} else {
					echo $weight[$mid];
				}
				echo "<input type='hidden' name='module[]' value='" . (int) $mid
				."' /><input type='hidden' name='oldname[" . $mid . "]' value='" . htmlspecialchars($oldname[$mid], ENT_QUOTES, _CHARSET)
				."' /><input type='hidden' name='newname[" . $mid . "]' value='" . htmlspecialchars($newname[$mid], ENT_QUOTES, _CHARSET)
				."' /><input type='hidden' name='oldstatus[" . $mid . "]' value='" . (int) $oldstatus[$mid]
				."' /><input type='hidden' name='newstatus[" . $mid . "]' value='" . (int) $newstatus[$mid]
				."' /><input type='hidden' name='oldweight[" . $mid . "]' value='" . (int) $oldweight[$mid]
				."' /><input type='hidden' name='weight[" . $mid . "]' value='" . (int) $weight[$mid]
				."' /></td></tr>";
			}

			echo "<tr class='foot' align='center'><td colspan='3'><input class='btn btn-primary' type='submit' value='"
			. _MD_AM_SUBMIT . "' />&nbsp;<input class='btn btn-warning' type='button' value='" . _MD_AM_CANCEL
			. "' onclick='location=\"admin.php?fct=modules\"' />" . icms::$security->getTokenHTML()
			. "</td></tr></table></form>";
			icms_cp_footer();
			break;

		case "submit":
			$ret = array();
			$write = false;
			foreach ($module as $mid) {
				if (isset($newstatus[$mid]) && $newstatus[$mid] == 1) {
					if ($oldstatus[$mid] == 0) {
						$ret[] = xoops_module_activate($mid);
					}
				} else {
					if ($oldstatus[$mid] == 1) {
						$ret[] = xoops_module_deactivate($mid);
					}
				}
				$newname[$mid] = trim($newname[$mid]);
				if ($oldname[$mid] != $newname[$mid] || $oldweight[$mid] != $weight[$mid]) {
					$ret[] = xoops_module_change($mid, $weight[$mid], $newname[$mid]);
					$write = true;
				}
				flush();
			}
			if ($write) {
				$contents = impresscms_get_adminmenu();
				if (!xoops_module_write_admin_menu($contents)) {
					$ret[] = "<p>" . _MD_AM_FAILWRITE . "</p>";
				}
			}
			icms_cp_header();
			if (count($ret) > 0) {
				foreach ($ret as $msg) {
					if ($msg != '') {
						echo $msg;
					}
				}
			}
			echo "<br /><a class='btn btn-primary' href='admin.php?fct=modules'>" . _MD_AM_BTOMADMIN . "</a>";
			icms_cp_footer();
			break;

		case 'install':
			$module_handler = icms::handler('icms_module');
			$mod = & $module_handler->create();
			$mod->loadInfoAsVar($module);
			if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
				$msgs = '<img src="' . ICMS_MODULES_URL . '/' . $mod->getVar('dirname') . '/' . trim($mod->getInfo('image')) . '" alt="" />';
			}
			$msgs .= '<br /><span style="font-size:smaller;">' . $mod->getVar('name') . '</span><br /><br />' . _MD_AM_RUSUREINS;
			if (empty($from_112)) {
				$from_112 = false;
			}
			icms_cp_header();
			icms_core_Message::confirm(array('module' => $module, 'op' => 'install_ok', 'fct' => 'modules', 'from_112' => $from_112), 'admin.php', $msgs, _MD_AM_INSTALL);
			icms_cp_footer();
			break;

		case 'install_ok':
			/**
			 * @var icms_module_Handler $module_handler
			 */
			$module_handler = icms::handler('icms_module');
			$logger = new \Psr\Log\NullLogger(); // TODO: change this to normal
			$module_handler->install(
				$module->getVar('dirname'),
				$logger
			);
			if ($from_112) {
				$module_handler->update(
					$module->getVar('dirname'),
					$logger
				);
			}
			$contents = impresscms_get_adminmenu();
			if (!xoops_module_write_admin_menu($contents)) {
				$ret[] = "<p>" . _MD_AM_FAILWRITE . "</p>";
			}
			icms_cp_header();
			if (count($ret) > 0) {
				foreach ($ret as $msg) {
					if ($msg != '') {
						echo $msg;
					}
				}
			}
			echo "<br /><a class='btn btn-primary' href='admin.php?fct=modules'>" . _MD_AM_BTOMADMIN . "</a>";
			icms_cp_footer();
			break;

		case 'uninstall':
			$module_handler = icms::handler('icms_module');
			$mod = & $module_handler->getByDirname($module);
			$mod->registerClassPath();

			if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
				$msgs = '<img src="' . ICMS_MODULES_URL . '/' . $mod->getVar('dirname') . '/' . trim($mod->getInfo('image')) . '" alt="" />';
			}
			$msgs .= '<br /><span style="font-size:smaller;">' . $mod->getVar('name') . '</span><br /><br />' . _MD_AM_RUSUREUNINS;
			icms_cp_header();
			icms_core_Message::confirm(array('module' => $module, 'op' => 'uninstall_ok', 'fct' => 'modules'), 'admin.php', $msgs, _YES);
			icms_cp_footer();
			break;

		case 'uninstall_ok':
			$module_handler = icms::handler('icms_module');
			$logger = new \Psr\Log\NullLogger(); // TODO: change this to normal
			$module_handler->uninstall(
				$module->getVar('dirname'),
				$logger
			);
			$contents = impresscms_get_adminmenu();
			if (!xoops_module_write_admin_menu($contents)) {
				$ret[] = "<p>" . _MD_AM_FAILWRITE . "</p>";
			}
			icms_cp_header();
			if (count($ret) > 0) {
				foreach ($ret as $msg) {
					if ($msg != '') {
						echo $msg;
					}
				}
			}
			echo "<a class='btn btn-primary' href='admin.php?fct=modules'>" . _MD_AM_BTOMADMIN . "</a>";
			icms_cp_footer();
			break;

		case 'update':
			$module_handler = icms::handler('icms_module');
			$mod = & $module_handler->getByDirname($module);
			if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
				$msgs = '<img src="' . ICMS_MODULES_URL . '/' . $mod->getVar('dirname') . '/' . trim($mod->getInfo('image')) . '" alt="" />';
			}
			$msgs .= '<br /><span style="font-size:smaller;">' . $mod->getVar('name') . '</span><br /><br />' . _MD_AM_RUSUREUPD;
			icms_cp_header();

			if (icms_getModuleInfo('system')->getDBVersion() < 14 && (!is_writable(ICMS_PLUGINS_PATH) || !is_dir(ICMS_ROOT_PATH . '/plugins/preloads') || !is_writable(ICMS_ROOT_PATH . '/plugins/preloads'))) {
				icms_core_Message::error(sprintf(_MD_AM_PLUGINSFOLDER_UPDATE_TEXT, ICMS_PLUGINS_PATH, ICMS_ROOT_PATH . '/plugins/preloads'), _MD_AM_PLUGINSFOLDER_UPDATE_TITLE, true);
			}
			if (icms_getModuleInfo('system')->getDBVersion() < 37 && !is_writable(ICMS_IMANAGER_FOLDER_PATH)) {
				icms_core_Message::error(sprintf(_MD_AM_IMAGESFOLDER_UPDATE_TEXT, str_ireplace(ICMS_ROOT_PATH, "", ICMS_IMANAGER_FOLDER_PATH)), _MD_AM_IMAGESFOLDER_UPDATE_TITLE, true);
			}

			icms_core_Message::confirm(array('module' => $module, 'op' => 'update_ok', 'fct' => 'modules'), 'admin.php', $msgs, _MD_AM_UPDATE);
			icms_cp_footer();
			break;

		case 'update_ok':
			$module_handler = icms::handler('icms_module');
			$logger = new \Psr\Log\NullLogger(); // TODO: change this to normal
			$module_handler->update(
				$module->getVar('dirname'),
				$logger
			);
			$contents = impresscms_get_adminmenu();
			if (!xoops_module_write_admin_menu($contents)) {
				$ret[] = "<p>" . _MD_AM_FAILWRITE . "</p>";
			}
			icms_cp_header();
			if (count($ret) > 0) {
				foreach ($ret as $msg) {
					if ($msg != '') {
						echo $msg;
					}
				}
			}
			echo "<br /><a class='btn btn-primary' href='admin.php?fct=modules'>" . _MD_AM_BTOMADMIN . "</a>";
			icms_cp_footer();
			break;

		default:
			break;
}