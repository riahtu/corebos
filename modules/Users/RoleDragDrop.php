<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/
require_once 'include/utils/UserInfoUtil.php';
$toid= vtlib_purify($_REQUEST['parentId']);
$fromid= vtlib_purify($_REQUEST['childId']);

global $adb,$mod_strings;
$result=$adb->pquery('select * from vtiger_role where roleid=?', array($toid));
$parentRoleList=$adb->query_result($result, 0, 'parentrole');
$replace_with=$parentRoleList;
$orgDepth=$adb->query_result($result, 0, 'depth');

//echo 'replace with is '.$replace_with;
//echo '<BR>org depth '.$orgDepth;
$parentRoles=explode('::', $parentRoleList);

if (in_array($fromid, $parentRoles)) {
	echo $mod_strings['ROLE_DRAG_ERR_MSG'];
	die;
}

$roleInfo=getRoleAndSubordinatesInformation($fromid);

$fromRoleInfo=$roleInfo[$fromid];
$replaceToStringArr=explode('::'.$fromid, $fromRoleInfo[1]);
$replaceToString=$replaceToStringArr[0];

$stdDepth=$fromRoleInfo['2'];

//Constructing the query
$query='update vtiger_role set parentrole=?,depth=? where roleid=?';
$recalculate = false;
foreach ($roleInfo as $mvRoleId => $mvRoleInfo) {
	$subPar=explode($replaceToString, $mvRoleInfo[1], 2);//we have to split as two elements only
	$mvParString=$replace_with.$subPar[1];
	$subDepth=$mvRoleInfo[2];
	$mvDepth=$orgDepth+(($subDepth-$stdDepth)+1);
	$adb->pquery($query, array($mvParString, $mvDepth, $mvRoleId));

	// Invalidate any cached information
	VTCacheUtils::clearRoleSubordinates($mvRoleId);
	$recalculate = true;
}
if ($recalculate) {
	RecalculateSharingRules();
}
header('Location: index.php?action=SettingsAjax&module=Settings&file=listroles&ajax=true');
?>
