<?php
/**
 * Administration of template sets, form file
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	Administration
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id$
 */

if ($tform['tpl_tplset'] != 'default') {
	$form = new icms_form_Theme(_MD_EDITTEMPLATE, 'template_form', 'admin.php', 'post', true);
} else {
	$form = new icms_form_Theme(_MD_VIEWTEMPLATE, 'template_form', 'admin.php', 'post', true);
}
$form->addElement(new icms_form_elements_Label(_MD_FILENAME, $tform['tpl_file']));
$form->addElement(new icms_form_elements_Label(_MD_FILEDESC, $tform['tpl_desc']));
$form->addElement(new icms_form_elements_Label(_MD_LASTMOD, formatTimestamp($tform['tpl_lastmodified'], 'l')));
$config = array(
	'name' => 'html',
	'value' => $tform['tpl_source'],
	'language' => _LANGCODE,
	'width' => '100%',
	'height' => '400px',
	'syntax' => 'html');
if ($tform['tpl_tplset'] == 'default') $config["is_editable"] = FALSE;
$tpl_src = icms_plugins_EditorHandler::getInstance('source')->get($icmsConfig['sourceeditor_default'], $config);
$tpl_src->setCaption(_MD_FILEHTML);
$form->addElement($tpl_src);
$form->addElement(new icms_form_elements_Hidden('id', $tform['tpl_id']));
$form->addElement(new icms_form_elements_Hidden('op', 'edittpl_go'));
$form->addElement(new icms_form_elements_Hidden('redirect', 'edittpl'));
$form->addElement(new icms_form_elements_Hidden('fct', 'tplsets'));
$form->addElement(new icms_form_elements_Hidden('moddir', $tform['tpl_module']));
if ($tform['tpl_tplset'] != 'default') {
	$button_tray = new icms_form_elements_Tray('');
	$button_tray->addElement(new icms_form_elements_Button('', 'previewtpl', _PREVIEW, 'submit'));
	$button_tray->addElement(new icms_form_elements_Button('', 'submittpl', _SUBMIT, 'submit'));
	$form->addElement($button_tray);
} else {
	$form->addElement(new icms_form_elements_Button('', 'previewtpl', _MD_VIEW, 'submit'));
}
?>