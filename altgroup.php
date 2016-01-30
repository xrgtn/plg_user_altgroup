<?php
/**
 * @author	xrgtn
 * license	Simplified BSD License
 */

defined('_JEXEC') or die;

/**
 * Joomla Alternative Registration Group plugin
 *
 * @since 3.1
 */
class PlgUserAltgroup extends JPlugin {
    public function onContentPrepareData($context, $data) {
	// Check we are manipulating a valid form.
	if (!in_array($context, array(
	'com_users.profile', 'com_users.user',
	'com_users.registration', 'com_admin.profile'))) {
	    return true;
	};

	if (is_object($data)) {
	    $userId = isset($data->id) ? $data->id : 0;
	    if (!isset($data->altgroups) and $userId > 0) {
		// Load the profile data from the database.
		$db = JFactory::getDbo();
		$db->setQuery("select ugm.group_id, g.title "
		    ." from #__user_usergroup_map ugm,"
		    ." #__usergroups g"
		    ." where ugm.user_id=".(int)$userId
		    ." and g.group_id=ugm.group_id");
		try {
		    $rows = $db->loadRowList();
		} catch (RuntimeException $e) {
		    $this->_subject->setError($e->getMessage());
		    return false;
		};
		// Merge the profile data.
		$data->altgroups = array();
		foreach ($rows as $r) {
		    error_log("group $r[0], $r[1]\n");
		    $data->altgroups[$r[0]] = $r[1];
		};
	    };
	};
	return true;
    }
};
/* vi: set sw=4 noet ts=8 tw=71: */
?>
