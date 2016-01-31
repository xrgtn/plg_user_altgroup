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
	$s = nl2br($s);
	$s = str_replace(" ", "&nbsp;", $s);
	return "<code>$s</code>";
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
	// We only work with com_users.* and com_admin.profiled
	// forms:
	$form_name = $form->getName();
	if (!in_array($form_name, array('com_admin.profile',
	'com_users.user', 'com_users.profile',
	'com_users.registration'))) {
	    return true;
	};

	// Add the registration fields to the form.
	//JForm::addFormPath(__DIR__ . '/profiles');
	//$form->loadFile('profile', false);


	// Change fields description when displayed in front-end or
	// back-end profile editing
	$app = JFactory::getApplication();

	if ($app->isSite()
	&& $form_name == 'com_users.registration') {
	    JLog::add("fieldsets=".self::_str(
		$form->getFieldsets()));
	    JLog::add("form control=".self::_str(
		$form->getFormControl()));
	    JLog::add("e2input=".self::_str(
		$form->getInput("email2")));
	    JLog::add("e2label=".self::_str(
		$form->getLabel("email2","")));
	    JLog::add("form=".self::_str($form));
	    $form->removeField("email2");
	    $grpoptions = "";
	    foreach (explode(",", $this->params->get('altgroups'))
	    as $grp) {
		$grp = htmlentities(trim($grp));
	        $grpoptions .= "      <option value=\"$grp\">I'm\n"
		    ."      $grp</option>\n";
	    };
	    $form->load("<form>\n"
		."  <fieldset name=\"altgroups\">\n"
		."    <field name=\"altgroup\" type=\"radio\"\n"
		."    label=\"who are you?\"\n"
		."    required=\"true\">\n"
		."$grpoptions"
		."    </field>\n"
		."  </fieldset>\n"
		."</form>");
	    /*
	    $form->setFieldAttribute('email2', 'label',
		'groups');
	    $form->setFieldAttribute('email2', 'default',
		$this->params->get('altgroups'));
	    $form->setFieldAttribute('email2', 'description',
		"one of ".$this->params->get('altgroups'));
	    $form->setFieldAttribute('email2', 'required',
		'false');*/
	} elseif ($form_name == 'com_users.profile') {
	    $form->removeField("email2");
	};

	return true;
    }
};
/* vi: set sw=4 noet ts=8 tw=71: */
?>
