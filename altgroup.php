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

	//JLog::add("onContentPrepareData ".htmlentities($context));

	if (is_object($data) and isset($data->id) and $data->id > 0
		and $context == 'com_users.profile'
		and !isset($data->altgroup)) {
	    // Load list of user's groups from the DB:
	    $db = JFactory::getDbo();
	    $db->setQuery("select ugm.group_id, g.title "
		." from #__user_usergroup_map ugm,"
		." #__usergroups g"
		." where ugm.user_id=".(int)$data->id
		." and g.id=ugm.group_id");
	    try {
		$rows = $db->loadRowList();
	    } catch (RuntimeException $e) {
		$this->_err($e->getMessage());
		return false;
	    };
	    $data->altgroup['groups'] = array();
	    foreach ($rows as $r) {
		$data->altgroup['groups'][] = array($r[0], $r[1]);
		/*JLog::add(htmlentities("$context: user ".$data->_id
		    .", group $r[0] - $r[1]"));*/
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

	//JLog::add("onContentPrepareForm ".htmlentities($form_name));

	$app = JFactory::getApplication();

	if ($app->isSite()
		&& $form_name == 'com_users.registration') {
	    $lang = JFactory::getLanguage()->getTag();
	    $form->removeField("email2");
	    $grpoptions = "";
	    foreach (explode(",", $this->params->get('altgroups'))
		    as $grp) {
		$grp = htmlentities(trim($grp));
	        $grpoptions .= "        <option value=\"$grp\">"
		    .($lang == "ru-RU" ? "Я -" : "I'm")
		    ." $grp</option>\n";
	    };
	    /* Append "altgroup.groupname" field to "default" fieldset
	     * so that it would be rendered in the same "block" as
	     * core user fields like "username/email/password": */
	    $form->load('<form>'
		.'  <fields name="altgroup">'
		.'    <fieldset name="default">'
		.'      <field name="groupname" type="radio"'
		.'          label='.($lang == "ru-RU" ?
		    '"Кто вы?"' : '"Who are you?"')
		.'          required="true">'
		.         $grpoptions
		.'      </field>'
		.'    </fieldset>'
		.'  </fields>'
		.'</form>');
	    /* JLog::add("input=".self::_str(
		$form->getInput("groupname", "altgroup")));
	    JLog::add("form=".self::_str($form));*/
	} elseif ($form_name == 'com_users.profile') {
	    $lang = JFactory::getLanguage()->getTag();
	    $form->removeField("email2");
	    $group_names = array();
	    if (isset($data->altgroup['groups'])) {
		foreach ($data->altgroup['groups'] as $g) {
		    $group_names[] = $g[1];
		};
	    };
	    /* Append "altgroup.groupnames" field to "core"
	     * fieldset: */
	    $form->load('<form>'
		.'  <fields name="altgroup">'
		.'    <fieldset name="core">'
		.'      <field name="groupnames" type="text"'
		.'          disabled="true"'
		.'          label='.($lang == "ru-RU" ?
		    '"Вы -"' : '"You are:"')
		.'          default="'.(htmlentities(implode(
		    ",", $group_names))).'"'
		.'      />'
		.'    </fieldset>'
		.'  </fields>'
		.'</form>');
	};

	return true;
    }

    /**
     * Find group by its title.
     *
     * @param   string  $title  Group title.
     * @return  array           List of group (id, title) pairs.
     */
    public static function findGroup($title) {
	$db = JFactory::getDbo();
	$db->setQuery("select id,title from #__usergroups"
	    ." where title=".$db->quote($title)
	    ." order by id");
	return $db->loadRowList();
    }

    /**
     * Logs the supplied message as warning.
     *
     * @param   string  $message  Warning text.
     *
     * @return  void
     */
    protected function _warn($message) {
	JLog::add(htmlentities($message), JLog::WARNING);
    }

    /**
     * Logs the supplied message as warning and sets it as an
     * error on the observed subject.
     *
     * @param   string  $message  Error message text.
     *
     * @return  void
     */
    protected function _err($message) {
	$this->_warn($message);
	$this->_subject->setError($message);
    }

    /**
     * Method is called before user data is stored in the database.
     * Altgroup plugin verifies that the chosen group is valid
     * and exists in the database.
     *
     * @param	array	$user	Holds the old user data.
     * @param	boolean	$isnew	True if a new user is stored.
     * @param	array	$data	Holds the new user data.
     *
     * @return	boolean
     */
    public function onUserBeforeSave($user, $isnew, $data) {
	$app = JFactory::getApplication();

	// Check that the "altgroup.groupname" is valid:
	if (!empty($data['altgroup']['groupname'])) {
	    $group_name = $data['altgroup']['groupname'];
	    $permitted = false;
	    foreach (explode(",", $this->params->get('altgroups'))
		    as $g) {
		if ($group_name == trim($g)) {
		    $permitted = true;
		    break;
		};
	    };
	    if (!$permitted) {
		$this->_err("Altgroup '$group_name' not permitted");
		return false;
	    };
	} elseif ($isnew and $app->isSite()) {
	    $this->_err("Empty altgroup name field");
	    return false;
	} else {
	    return true;
	};

	if ($isnew) {
	    // verify altgroup.groupname against the DB and reset
	    // $data['groups'] array to altgroup's group_id:
	    try {
		$groups = self::findGroup($group_name);
	    } catch (RuntimeException $e) {
		$this->_err("DB error: ".$e->getMessage());
		return false;
	    };
	    if (count($groups) > 1) {
		// XXX: when multiple groups match the name,
		// we take the one with the smallest id.
		$ids = array();
		foreach ($groups as $g) {$ids[] = $g[0];};
		$this->warn("Multiple groups named '$group_name'"
		    ." found: ".implode(", ", $ids));
	    } elseif (count($groups) == 0) {
		$this->_err("No group named '$group_name'"
		    ." found in DB");
		return false;
	    };
	};
    }

    /**
     * Called after user data has been saved. Because the data
     * passed to the earlier onUserBeforeSave() call are copies
     * and modifications to them won't affect JUser and JUserTable
     * objects, we alter primary user's group in onUserAfterSave().
     *
     * @param   array    $data    entered user data
     * @param   boolean  $isNew   true if this is a new user
     * @param   boolean  $ok      true if saving the user worked
     * @param   string   $error   error message
     *
     * @return  bool
     */
    public function onUserAfterSave($data, $isNew, $ok, $error) {
	// Only "fix" primary group for successfully saved new users:
	if ($isNew && $ok && isset($data['altgroup']['groupname'])) {
	    $u = (int)JArrayHelper::getValue($data, 'id', 0, 'int');
	    $group_name = $data['altgroup']['groupname'];
	    try {
		$groups = self::findGroup($group_name);
		if (count($groups) < 1) {
		    $this->_err("No group named '$group_name'"
			." found in DB");
		    return false;
		};
		$g = (int)$groups[0][0];
		$db = JFactory::getDbo();
		$db->setQuery("delete from #__user_usergroup_map"
		    ." where user_id=$u");
		$db->execute();
		$db->setQuery("insert into #__user_usergroup_map"
		    ."(user_id, group_id) values ($u, $g)");
		$db->execute();
	    } catch (RuntimeException $e) {
		$this->_err("DB error: ".$e->getMessage());
		return false;
	    };
	};
	return true;
    }
};
/* vi: set sw=4 noet ts=8 tw=71: */
?>
