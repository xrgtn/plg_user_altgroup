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
	// We only work with com_users.* and com_admin.profile:
	if (!in_array($context, array(
		'com_admin.profile', 'com_users.user',
		'com_users.profile', 'com_users.registration'))) {
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
		    JLog::add(htmlentities("user $userId,"
			." group $r[0] - $r[1]"));
		    $data->altgroups[$r[0]] = $r[1];
		};
	    };
	};
	return true;
    }

    private static function _str($obj) {
	$s = htmlentities(print_r($obj, 1));
	$ret = "";
	foreach (explode("\n", $s) as $l) {
	    if (preg_match('/^( +)(.*)$/', $l, $g)) {
		$l = str_replace(" ", "&nbsp;", $g[1]).$g[2];
	    };
	    $ret .= ($ret == "") ? "" : "<br>";
	    $ret .= $l;
	};
	return "<tt>$ret</tt>";
    }

    /**
     * Adds additional fields to the user editing form
     *
     * @param   JForm  $form  The form to be altered.
     * @param   mixed  $data  The associated data for the form.
     *
     * @return  boolean
     */
    public function onContentPrepareForm($form, $data) {
	if (!($form instanceof JForm)) {
	    $this->_subject->setError('JERROR_NOT_A_FORM');
	    return false;
	};

	// We only work with com_users.* and com_admin.profile:
	$form_name = $form->getName();
	if (!in_array($form_name, array(
		'com_admin.profile', 'com_users.user',
		'com_users.profile', 'com_users.registration'))) {
	    return true;
	};

	$app = JFactory::getApplication();

	if ($app->isSite()
		&& $form_name == 'com_users.registration') {
	    $form->removeField("email2");
	    $grpoptions = "";
	    foreach (explode(",", $this->params->get('altgroups'))
		    as $grp) {
		$grp = htmlentities(trim($grp));
	        $grpoptions .= "        <option value=\"$grp\">"
		    ."I'm $grp</option>\n";
	    };
	    /* Append "altgroup.groupname" field to "default" fieldset
	     * so that it would be rendered in the same "block" as
	     * core user fields like "username/email/password": */
	    $form->load("<form>"
		."  <fields name='altgroup'>"
		."    <fieldset name='default'>"
		."      <field name='groupname' type='radio'"
		."          label='Who are you?'"
		."          required='true'>"
		.         $grpoptions
		."      </field>"
		."    </fieldset>"
		."  </fields>"
		."</form>");
	    /* JLog::add("input=".self::_str(
		$form->getInput("groupname", "altgroup")));
	    JLog::add("form=".self::_str($form));*/
	} elseif ($form_name == 'com_users.profile') {
	    $form->removeField("email2");
	};

	return true;
    }
};
/* vi: set sw=4 noet ts=8 tw=71: */
?>
