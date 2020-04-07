<?php
/**
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 * @author      Magnus Hasselquist <magnus.hasselquist@gmail.com> - http://mintekniskasida.blogspot.se/
 */
 
// No direct access
defined('_JEXEC') or die;

function db_field_replace($before_str, $user_id) {
	$db = JFactory::getDbo();
	// $query = "SET CHARACTER SET utf8";
	// $db->setQuery($query);
	$query = "select * from #__users inner join #__comprofiler on #__users.id = #__comprofiler.user_id WHERE #__users.id =".$user_id;
	// echo $query;
	$db->setQuery($query);
	$person = $db->loadAssoc();
	// get all the fields that could possibly be part of template to be replaced to get us something to loop through. Also add id and user_id as fields.
	$query = "SELECT name FROM #__comprofiler_fields WHERE #__comprofiler_fields.table = '#__users' OR #__comprofiler_fields.table = '#__comprofiler' UNION SELECT 'id' AS name UNION SELECT 'user_id' AS name";
	$db->setQuery($query);
	$fields = $db->loadAssocList();

	$after_str = $before_str;
	// echo $before_str;
	if (!empty($fields)){
		foreach ($fields as $field) { //for every field that may be in the before_str
			$paramtofind = "[".$field['name']."]";
			$fieldtouse = $field['name'];
			if (isset($person[$fieldtouse])) {
				$datatoinsert = $person[$fieldtouse];
	 			$after_str = str_ireplace($paramtofind, $datatoinsert, $after_str);
	 		} else {
	 			$after_str = str_ireplace($paramtofind, '', $after_str); // replace the param name with '' if not found.
			};
		}
	}
	return $after_str;
}

class modHelloWorldHelper
{
    /**
     * Retrieves the Result
     *
     * @param array $params An object containing the module parameters
     * @access public
     */

	public static function getHello( $params )
	{
    		$result=''; //reset result
		// Get the parameters
		$list_id = $params->get('listid');
		$list_template = $params->get('template');
		$list_textabove = $params->get('text-above');
		$list_textbelow = $params->get('text-below');

		// Obtain a database connection
		$db = JFactory::getDbo();
		// Lets make sure to support åäö
		// $query = "SET CHARACTER SET utf8";
		// $db->setQuery($query);

		// Retrieve the selected list
		$query = $db->getQuery(true)
		->select('params')
		->select('usergroupids')		
		->from('#__comprofiler_lists')
		->where('listid = '. $list_id . ' AND published=1')
			->order('ordering ASC');
		// echo $query;
		$db->setQuery($query);

		// Load the List row.
		$row = $db->loadAssoc();
		$select_sql_raw = $row['params'];
		$select_sql =""; //declare variable		

		// echo "RAW :".$select_sql_raw."<br/>"; //DEBUG

		// Process the filterfields to make ut useful for next query
		// CB19 $select_sql = utf8_encode(substr(urldecode($select_sql_raw), 2, -1));
		$json_a=json_decode($select_sql_raw,true);
		$filters_basic = $json_a['filter_basic'];
		$filter_advanced = $json_a['filter_advanced'];
		if ($filters_basic <>'') {
        	foreach($filters_basic as $filter) {
                     $select_sql .= $filter['column'] . " " . $filter['operator']. " '" . $filter['value'] ."' AND ";
        	}
		$select_sql = substr($select_sql, 0, -5); //rensa bort den sista AND
		}
		if ($filter_advanced <> '') {
	             $select_sql = $filter_advanced;
	    	}

	$userlistorder = $json_a['sort_basic'][0]['column'] . " " . $json_a['sort_basic'][0]['direction'];
	// echo "ORDER: " . $userlistorder .".";
	// echo "DEC :".$select_sql;

	// Set a base-sql for connecting users, fields and lists
// OLD	$fetch_sql = "select * from #__users inner join #__comprofiler on #__users.id = #__comprofiler.user_id where #__users.block = 0"; //TODO check block or something else?
        $usergroupids = str_replace("|*|", ",", $row['usergroupids']); //CMJ ADDED

        $list_show_unapproved = $json_a['list_show_unapproved'];
        $list_show_blocked = $json_a['list_show_blocked'];
        $list_show_unconfirmed = $json_a['list_show_unconfirmed'];
        $fetch_sql = "SELECT u.*, ue.* FROM #__users u JOIN #__user_usergroup_map g ON g.`user_id` = u.`id` JOIN #__comprofiler ue ON ue.`id` = u.`id` WHERE g.group_id IN (".$usergroupids.")";
        if ($list_show_blocked == 0) {$fetch_sql.=" AND u.block = 0 ";}
        if ($list_show_unapproved == 0) {$fetch_sql.=" AND ue.approved = 1 ";} 
        if ($list_show_unconfirmed == 0) {$fetch_sql.=" AND ue.confirmed = 1 ";}


	// add "having" only if needed
//OLD	if ($select_sql <>' ') $fetch_sql = $fetch_sql . " HAVING ";
        if ($select_sql <>'') $fetch_sql = $fetch_sql . " AND (" . $select_sql . ")";

	// Combine the final SQL for the selected list
//OLD 	$fetch_sql = $fetch_sql . $select_sql;

	// echo $fetch_sql . "<br>";

	//Add ordering if list is configured for that
	if ($userlistorder <>'') { $fetch_sql .= " ORDER BY ".$userlistorder; }

	// Now, lets use the final SQL to get all Users from Joomla/CB
	$query = $fetch_sql;
	$db->setQuery($query);
	$persons = $db->loadAssocList();
	if (!empty($persons)){
		foreach ($persons as $person) { //for every person that is a reciever, lets do an email.
		 	// $result .= $person['username']."<br/>";
		 	// Lets loop over the Users and create the output using the Template, replacing [fileds] in Template
			$result .=  db_field_replace($list_template, $person['id']);
		}
	}
	$resultcomplete = $list_textabove . $result . $list_textbelow;
	return $resultcomplete;

    	}
}
?>
