<?php
/**
 * @version		$Id: query.php 14401 2010-01-26 14:10:00Z louis $
 * @package		Joomla
 * @subpackage	Content
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.helper');
/**
 * Content Component Query Helper
 *
 * @static
 * @package		Joomla
 * @subpackage	Content
 * @since 1.5
 */
class EmundusHelperFilters{
	
	/*
	** @description Clear session and reinit values by default
	*/
	function clear() {
		global $option;

		$mainframe =& JFactory::getApplication();
		
		$menu = &JSite::getMenu();
		$current_menu  = $menu->getActive();
		$menu_params = $menu->getParams($current_menu->id);
		
		$filts_names 	= explode(',', $menu_params->get('em_filters_names'));
		$filts_values 	= explode(',', $menu_params->get('em_filters_values'));
		$filts_details 	= array('profile'			=> NULL,
							   'evaluator'			=> NULL,
							   'evaluator_group'	=> NULL,
							   'schoolyear'			=> NULL,
							   'missing_doc'		=> NULL,
							   'complete'			=> NULL,
							   'finalgrade'			=> NULL,
							   'validate'			=> NULL,
							   'other'				=> NULL);
		$i = 0;
		foreach ($filts_names as $filt_name)
			if (array_key_exists($i, $filts_values))
				$filts_details[$filt_name] = $filts_values[$i++];
			else
				$filts_details[$filt_name] = '';
		unset($filts_names); unset($filts_values);
		
		$mainframe->setUserState( $option."filter_order", "" );
		$mainframe->setUserState( $option."filter_order_Dir", "" );
		$mainframe->setUserState( $option."schoolyears", EmundusHelperFilters::getCurrentCampaign() );
		$mainframe->setUserState( $option."elements", array() );
		$mainframe->setUserState( $option."elements_values", array() );
		$mainframe->setUserState( $option."elements_other", array() );
		$mainframe->setUserState( $option."elements_values_other", array() );
		$mainframe->setUserState( $option."finalgrade", $filts_details['finalgrade'] );
		$mainframe->setUserState( $option."s", "" );
		$mainframe->setUserState( $option."groups", $filts_details['evaluator_group'] );
		$mainframe->setUserState( $option."user", $filts_details['evaluator'] );
		$mainframe->setUserState( $option."profile", $filts_details['profile'] );
		$mainframe->setUserState( $option."missing_doc", $filts_details['missing_doc'] );
		$mainframe->setUserState( $option."complete", $filts_details['complete'] );
		$mainframe->setUserState( $option."validate", $filts_details['validate'] );
		
		$this->setRedirect('index.php?option=com_emundus&view='.JRequest::getCmd( 'view' ).'&Itemid='.JRequest::getCmd( 'Itemid' ));
	}
	/*
	* @param 			query results
	* @param 	array 	values to extract and insert
	*/
	function insertValuesInQueryResult($results, $options) {
		foreach ($results as $key=>$result) {
			if (array_key_exists('params', $result)) {
				if (is_array($result)) {
					$params = json_decode($result['params']);
					foreach ($options as $option) {
						if (property_exists($params, 'sub_options') && array_key_exists($option, $params->sub_options))
							$results[$key][$option] = implode('|', $params->sub_options->$option);
						else
							$results[$key][$option] = '';
					}
				}
				else {
					$params = json_decode($result->params);
					foreach ($options as $option) {
						if (property_exists($params, 'sub_options') && array_key_exists($option, $params->sub_options))
							$results[$key]->$option = implode('|', $params->sub_options->$option);
						else
							$results[$key]->$option = '';
					}
				}
			}
		}
		return $results;
	}

	function getCurrentCampaign(){
		$db =& JFactory::getDBO();
		$query = 'SELECT DISTINCT schoolyear 
				FROM #__emundus_setup_profiles 
				WHERE published=1 
				ORDER BY schoolyear';
		$db->setQuery( $query );
		return $db->loadResultArray();
	}

	function getApplicants(){
		$db =& JFactory::getDBO();
		$query = 'SELECT esp.id, esp.label
		FROM #__emundus_setup_profiles esp 
		WHERE esp.published =1';
		$db->setQuery( $query );
		return $db->loadObjectList('id');
	}
	
	function getProfiles(){
		$db =& JFactory::getDBO();
		$query = 'SELECT esp.id, esp.label, esp.acl_aro_groups, caag.lft 
		FROM #__emundus_setup_profiles esp 
		INNER JOIN #__usergroups caag on esp.acl_aro_groups=caag.id 
		ORDER BY caag.lft, esp.label';
		$db->setQuery( $query );
		return $db->loadObjectList('id');
	}
	
	function getEvaluators(){
		$db =& JFactory::getDBO();
		$query = 'SELECT u.id, u.name
		FROM #__users u
		JOIN #__emundus_users_profiles eup ON u.id=eup.user_id
		JOIN #__emundus_setup_profiles esp ON esp.id=eup.profile_id
		WHERE esp.is_evaluator=1';
		$db->setQuery( $query );
		return $db->loadObjectList('id');
	}
	
	function getGroupsEval(){
		$db =& JFactory::getDBO();
		$query = 'SELECT ege.group_id
		FROM #__emundus_groups_eval ege
		ORDER BY ege.group_id';
		echo str_replace("#_", "jos", $query);
		$db->setQuery( $query );
		return $db->loadObjectList();
	}

	function getGroups(){
		$db =& JFactory::getDBO();
		$query = 'SELECT esg.id, esg.label  
		FROM #__emundus_setup_groups esg
		WHERE esg.published=1 
		ORDER BY esg.label';
		$db->setQuery( $query );
		return $db->loadObjectList('id');
	}
	
	function getSchoolyears(){
		$db =& JFactory::getDBO();
		$query = 'SELECT DISTINCT(schoolyear) as schoolyear
			FROM #__emundus_users 
			WHERE schoolyear is not null 
			AND schoolyear != "" 
			ORDER BY schoolyear';
		$db->setQuery( $query );
		return $db->loadResultArray();
	}
	
	function getFinal_grade(){
		$db =& JFactory::getDBO();
		$query = 'SELECT name, params FROM #__fabrik_elements WHERE name like "final_grade" LIMIT 1';
		$db->setQuery( $query );
		return EmundusHelperFilters::insertValuesInQueryResult($db->loadAssocList('name'), array("sub_values", "sub_labels"));
	}
	
	function getMissing_doc(){
		$db =& JFactory::getDBO();
		$query = 'SELECT DISTINCT(esap.attachment_id), esa.value
				FROM #__emundus_setup_attachment_profiles esap
				LEFT JOIN #__emundus_setup_attachments esa ON esa.id = esap.attachment_id';
		$db->setQuery( $query );
		return $db->loadObjectList();
	}
	
	function getElements(){
		$db =& JFactory::getDBO();
		$query = 'SELECT distinct(concat_ws("_",tab.db_table_name,element.name)), element.name AS element_name, element.label AS element_label, element.plugin AS element_plugin, element.id, groupe.id AS group_id, groupe.label AS group_label, element.params AS element_attribs,
				INSTR(groupe.params,\'"repeat_group_button":"1"\') AS group_repeated, tab.id AS table_id, tab.db_table_name AS table_name, tab.label AS table_label
				FROM #__fabrik_elements element 
				INNER JOIN #__fabrik_groups AS groupe ON element.group_id = groupe.id 
				INNER JOIN #__fabrik_formgroup AS formgroup ON groupe.id = formgroup.group_id 
				INNER JOIN #__fabrik_lists AS tab ON tab.form_id = formgroup.form_id 
				INNER JOIN #__fabrik_forms AS form ON tab.form_id = form.id 
				INNER JOIN #__menu AS menu ON form.id = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("formid=",menu.link)+7, 3), "&", 1)
				WHERE tab.published = 1 
				AND (tab.created_by_alias = "form")
					AND element.published=1 
					AND element.hidden=0 
					AND element.label!=" " 
					AND element.label!=""  
				ORDER BY menu.ordering, formgroup.ordering, groupe.id, element.ordering';
		$db->setQuery( $query );
		return $db->loadObjectList('id');
	}
	
	function getElementsOther($tables){
		$db =& JFactory::getDBO();
		$query = 'SELECT distinct(concat_ws("_",tab.db_table_name,element.name)), element.name AS element_name, element.label AS element_label, element.plugin AS element_plugin, element.id, groupe.id as group_id, groupe.label AS group_label, element.params AS element_attribs,
			INSTR(groupe.params,\'"repeat_group_button":"1"\') AS group_repeated, tab.id AS table_id, tab.db_table_name AS table_name, tab.label AS table_label 
				FROM #__fabrik_elements element
				INNER JOIN #__fabrik_groups AS groupe ON element.group_id = groupe.id 
				INNER JOIN #__fabrik_formgroup AS formgroup ON groupe.id = formgroup.group_id 
				INNER JOIN #__fabrik_lists AS tab ON tab.form_id = formgroup.form_id';
		if(!empty($tables) && !empty($tables[0])) {
			$query .= ' WHERE tab.id IN(';
			$first = true;
			foreach($tables as $table){
				if ($first){
					$query .= $table;
					$first = false;
				}
				else $query .= ', '.$table;
			}
			$query .= ') AND ';
		}
		else
			$query .= ' WHERE ';
		$query .= 'element.name NOT IN ("id", "time_date", "user", "student_id", "type_grade", "final_grade")
				ORDER BY group_id';
		$db->setQuery($query);
//		die(str_replace("#_", "jos", $query));
		return $db->loadObjectList();
	}
	
	function getElementsValuesOther($element_id){
		//jimport( 'joomla.registry.format.json' );
		$db =& JFactory::getDBO();
		$query = 'SELECT params FROM #__fabrik_elements element WHERE id='.$element_id;
		$db->setQuery($query);
		$res = $db->loadResult();
		$sub = json_decode($res);//JRegistryFormatJson::stringToObject($res);

		return $sub->sub_options;
	}

	function getElementsName($elements_id){
		$db =& JFactory::getDBO();
		$query = 'SELECT element.name AS element_name, element.id, tab.db_table_name AS tab_name, tab.created_by_alias AS created_by_alias
				FROM #__fabrik_elements element
				INNER JOIN #__fabrik_groups AS groupe ON element.group_id = groupe.id 
				INNER JOIN #__fabrik_formgroup AS formgroup ON groupe.id = formgroup.group_id 
				INNER JOIN #__fabrik_lists AS tab ON tab.form_id = formgroup.form_id
				WHERE element.id IN ('.$elements_id.')';
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	function buildOptions($element_name, $params){
		if(!empty($params->join_key_column)) {
			$db =& JFactory::getDBO();
			if($element_name == 'result_for')
				$query = 'SELECT '.$params->join_key_column.' AS elt_key, '.$params->join_val_column.' AS elt_val FROM '.$params->join_db_name.' WHERE published=1';
			else
				$query = 'SELECT '.$params->join_key_column.' AS elt_key, '.$params->join_val_column.' AS elt_val FROM '.$params->join_db_name.' '.$params->database_join_where_sql;
			$db->setQuery($query);
			$result = $db->loadObjectList(); 
		} else {
			$i = 0;
			foreach ($params->sub_options->sub_values as $value) {
				$result[] = (object) array('elt_key'=>$value, 'elt_val'=>$params->sub_options->sub_labels[$i]); 
				$i++;
			}
		}
		return $result;
	}
	
	function setSearchBox($selected, $search_value, $elements_values) { 
		jimport( 'joomla.html.parameter' );
	//echo $selected->element_plugin;
		$current_filter = "";
		if(!empty($selected)) {
			if($selected->element_plugin == "databasejoin"){
				$query_paramsdefs = JPATH_BASE.DS.'plugins'.DS.'fabrik_element'.DS.'databasejoin'.DS.'field.xml';
				$query_params = new JParameter($selected->element_attribs, $query_paramsdefs);
				$query_params = json_decode($query_params);
				$option_list =  EmundusHelperFilters::buildOptions($selected->element_name, $query_params);
				$current_filter .= '<select name="'.$elements_values.'[]" id="'.$elements_values.'" onChange="javascript:submit()">
				<option value="">'.JText::_('PLEASE_SELECT').'</option>';
				foreach($option_list as $value){
					$current_filter .= '<option value="'.$value->elt_key.'"';
					if ($value->elt_key == $search_value) $current_filter .= ' selected';
					$current_filter .= '>'.$value->elt_val.'</option>';
				}
				$current_filter .= '</select>';
			} elseif($selected->element_plugin == "checkbox" || $selected->element_plugin == "radiobutton"){
				$query_paramsdefs = JPATH_BASE.DS.'plugins'.DS.'fabrik_element'.DS.$selected->element_plugin.DS.'field.xml';
				$query_params = new JParameter($selected->element_attribs, $query_paramsdefs);
				$query_params = json_decode($query_params); 
				$option_list =  EmundusHelperFilters::buildOptions($selected->element_name, $query_params);
				$current_filter .= '<select name="'.$elements_values.'[]" id="'.$elements_values.'" onChange="javascript:submit()">
				<option value="">'.JText::_('PLEASE_SELECT').'</option>';
				foreach($option_list as $value){
					$current_filter .= '<option value="'.$value->elt_key.'"';
					if ($value->elt_key == $search_value) $current_filter .= ' selected';
					$current_filter .= '>'.$value->elt_val.'</option>';
				}
				$current_filter .= '</select>';
			} else
				$current_filter .= '<input name="'.$elements_values.'[]" width="30" value="'.$search_value.'" />';
		}
		/*else if (!empty($selected)){
			$elements_att =& EmundushelperFilters::getElementsValuesOther($selected->id);
			$sub_values = $elements_att->sub_values;//explode('|', $elements_att[0]->sub_values);
			$sub_labels = $elements_att->sub_labels;//explode('|', $elements_att[0]->sub_labels);
			$current_filter .= '<select name="'.$elements_values.'[]" id="'.$elements_values.'" onChange="javascript:submit()">
			<option value="">'.JText::_('PLEASE_SELECT').'</option>';
			$j = 0;

			foreach($sub_values as $value) {
				$current_filter .= '<option value="'.$value.'"';
				if($value == $search_value) $current_filter .= ' selected';
				$current_filter .= '>'.$sub_labels[$j].'</option>';
				$j++;
			}
			$current_filter .= '</select>';
		}*/
		return $current_filter;
	}

	/*
	** @description : Create a fieldset of filter boxes
	** @param array : Filters values indexed by filters names
	** @param array : Filters options indexed by filters names
	** @param array : List of the tables contained in "Other filters" dropbox
	*/	
	function createFilterBlock($params, $types, $tables){
		global $option;

		$mainframe =& JFactory::getApplication();
		
		$current_s 				= $mainframe->getUserStateFromRequest(  $option.'s', 's' );
		$current_profile		= $mainframe->getUserStateFromRequest(  $option.'profile', 'profile', $params['profile'] );
		$current_eval			= $mainframe->getUserStateFromRequest(  $option.'user', 'user', $params['evaluator'] );
		$current_group			= $mainframe->getUserStateFromRequest(  $option.'groups', 'groups', $params['evaluator_group'] );
		$miss_doc				= $mainframe->getUserStateFromRequest(  $option.'missing_doc', 'missing_doc', $params['missing_doc'] );
		$current_finalgrade		= $mainframe->getUserStateFromRequest(  $option.'finalgrade', 'finalgrade', $params['finalgrade'] );
		$current_schoolyear		= $mainframe->getUserStateFromRequest(  $option.'schoolyears', 'schoolyears', EmundusHelperFilters::getSchoolyears() );
		$search					= $mainframe->getUserStateFromRequest(  $option.'elements', 'elements' );
		$search_values			= $mainframe->getUserStateFromRequest(  $option.'elements_values', 'elements_values' );
		$search_other		 	= $mainframe->getUserStateFromRequest(  $option.'elements_other', 'elements_other' );
		$search_values_other	= $mainframe->getUserStateFromRequest(  $option.'elements_values_other', 'elements_values_other' );
		$complete_application	= $mainframe->getUserStateFromRequest(  $option.'complete', 'complete', $params['complete'] );
		$validate_application	= $mainframe->getUserStateFromRequest(  $option.'validate', 'validate', $params['validate'] );
		
		$option;
		$filters = '<fieldset><legend><img src="'.JURI::Base().'media/com_emundus/images/icones/viewmag_22x22.png" alt="'.JText::_('FILTERS').'"/>'.JText::_('FILTERS').'</legend>';
		
		$quick = '<div id="quick"><div class="em_label"><label><span class="editlinktip hasTip" title="'.JText::_('NOTE').'::'.JText::_('NAME_EMAIL_USERNAME').'">'.JText::_('QUICK_FILTER').'</span></label></div>';
		$quick .= '<div class="em_filtersElement"><input type="text" name="s" size="30" value="'.$current_s.'"/></div></div>';
		$filters .= $quick;
		
		if($params['profile'] !== NULL){
			$profile = '';
			if ($types['profile'] != 'hidden') $profile .= '<div class="em_filters" id="profile">
															<div class="em_label"><label>'.JText::_('PROFILE').'</label></div>
															<div class="em_filtersElement">';
			$profile .= '<select name="profile" '.($types['profile'] == 'hidden' ? 'style="visibility:hidden" ' : '').'onChange="javascript:submit()">
						 <option value="0">'.JText::_('ALL').'</option>';
			$profiles = EmundusHelperFilters::getApplicants();
			foreach($profiles as $prof) { 
				$profile .= '<option value="'.$prof->id.'"';
				if($current_profile == $prof->id) $profile .= ' selected';
				$profile .= '>'.$prof->label.'</option>'; 
			}
			$profile .= '</select>';
			if ($types['profile'] != 'hidden') $profile .= '</div></div>';
			$filters .= $profile;
		}
		//if($debug==1) $div .= '<input name="view_calc" type="checkbox" onclick="document.pressed=this.name" value="1" '.$view_calc==1?'checked=checked':''.' />';
		
		if($params['evaluator'] !== NULL){
			$eval = '';
			if ($types['evaluator'] != 'hidden') $eval .= '<div class="em_filters" id="evaluator">
														   <div class="em_label"><label>'.JText::_('ASSESSOR_USER_FILTER').'</label></div>
														   <div class="em_filtersElement">';
			$eval .= '<select name="user" '.($types['evaluator'] == 'hidden' ? 'style="visibility:hidden" ' : '').'onChange="javascript:submit()">
					  <option value="0">'.JText::_('ALL').'</option>';
			$evaluators = EmundusHelperFilters::getEvaluators();
			foreach($evaluators as $evaluator) { 
				$eval .= '<option value="'.$evaluator->id.'"';
				if($current_eval == $evaluator->id) $eval .= ' selected';
				$eval .= '>'.$evaluator->name.'</option>'; 
			}
			$eval .= '</select>';
			if ($types['evaluator'] != 'hidden') $eval .= '</div></div>';
			$filters .= $eval;
		}
		
		if($params['evaluator_group'] !== NULL){
			$group_eval = '';
			if ($types['evaluator_group'] != 'hidden') $group_eval .= '<div class="em_filters" id="gp_evaluator">
																	   <div class="em_label"><label>'.JText::_('ASSESSOR_GROUP_FILTER').'</label></div>
																	   <div class="em_filtersElement">';
			$group_eval .= '<select name="groups" '.($types['evaluator_group'] == 'hidden' ? 'style="visibility:hidden" ' : '"" ').'onChange="javascript:submit()">
							<option value="0">'.JText::_('ALL').'</option>'; 
			$groups = EmundusHelperFilters::getGroups();
			foreach($groups as $group) { 
				$group_eval .= '<option value="'.$group->id.'"';
				if($current_group == $group->id) $group_eval .= ' selected';
				$group_eval .= '>'.$group->label.'</option>'; 
			}
			$group_eval .= '</select>';
			if ($types['evaluator_group'] != 'hidden') $group_eval .= '</div></div>';
			$filters .= $group_eval;
		}
		
		if($params['finalgrade'] !== NULL){
			$finalgrade = EmundusHelperFilters::getFinal_grade();
			$final_gradeList = explode('|', $finalgrade['final_grade']['sub_labels']);
			$sub_values = explode('|', $finalgrade['final_grade']['sub_values']);
			foreach($sub_values as $sv) $p_grade[]="/".$sv."/";
			unset($sub_values);
			$final_grade = '';
			if ($types['finalgrade'] != 'hidden') $final_grade .= '<div class="em_filters" id="finalgrade">
																   <div class="em_label"><label>'.JText::_('FINAL_GRADE_FILTER').'</label></div>
																   <div class="em_filtersElement">';
			$final_grade .= '<select name="finalgrade" '.($types['finalgrade'] == 'hidden' ? 'style="visibility:hidden" ' : '').'onChange="javascript:submit()">
							 <option value="0">'.JText::_('PLEASE_SELECT').'</option>';  
							$groupe ="";
							for($i=0; $i<count($final_gradeList); $i++) { 
								$val = substr($p_grade[$i],1,1);
								$final_grade .= '<option value="'.$val.'"';
								if($val == $current_finalgrade) $final_grade .= ' selected';
								$final_grade .= '>'.$final_gradeList[$i].'</option>'; 
							} 
							unset($val); unset($i);
			$final_grade .= '</select>';
			if ($types['finalgrade'] != 'hidden') $final_grade .= '</div></div>';
			$filters .= $final_grade;
		}
		
		if($params['schoolyear'] !== NULL){
			$schoolyearList =& EmundusHelperFilters::getSchoolyears();
			$schoolyear = '';
			if ($types['schoolyear'] != 'hidden') $schoolyear .= '<div class="em_filters" id="schoolyear">
																  <div class="em_label"><label>'.JText::_('SCHOOLYEARS').'</label></div>
																  <div class="em_filtersElement">';
			$schoolyear .= '<select name="schoolyears[]" '.($types['schoolyear'] == 'hidden' ? 'style="visibility:hidden" ' : '');
			$schoolyear .= 'onChange="javascript:submit()" multiple="multiple" size="3">';
			//$schoolyear .= '<option value="">'.JText::_('ALL').'</option>';
			foreach($schoolyearList as $s) { 
				$schoolyear .= '<option value="'.$s.'"';
				if(!empty($current_schoolyear) && in_array($s,$current_schoolyear)) $schoolyear .= ' selected';
				$schoolyear .= '>'.$s.'</option>'; 
			}
			$schoolyear .= '</select>';
			if ($types['schoolyear'] != 'hidden') $schoolyear .= '</div></div>';
			$filters .= $schoolyear;
		}
		
		if($params['missing_doc'] !== NULL){
			$missing_docList =& EmundusHelperFilters::getMissing_doc();
			$missing_doc = '';
			if ($types['missing_doc'] != 'hidden') $missing_doc .= '<div class="em_filters" id="missing_doc"><div class="em_label">
																	<label>'.JText::_('MISSING_DOC').'</label></div>
																	<div class="em_filtersElement">';
			$missing_doc .= '<select name="missing_doc" '.($types['missing_doc'] == 'hidden' ? 'style="visibility:hidden" ' : '').'onChange="javascript:submit()">
							<option value="0">'.JText::_('ALL').'</option>'; 
			foreach($missing_docList as $md) { 
				$missing_doc .= '<option value="'.$md->attachment_id.'"';
				if($miss_doc == $md->attachment_id) $missing_doc .= ' selected';
				$missing_doc .= '>'.$md->value.'</option>'; 
			}
			$missing_doc .= '</select>';
			if ($types['schoolyear'] != 'hidden') $missing_doc .= '</div></div>';
			$filters .= $missing_doc;
		}
		
		if($params['complete'] !== NULL){
			$complete = '';
			if ($types['complete'] != 'hidden') $complete .= '<div class="em_filters" id="complete">
																 <div class="em_label"><label>'.JText::_('COMPLETE_APPLICATION').'</label></div>
																 <div class="em_filtersElement">';
			$complete .= '<select name="complete" '.($types['complete'] == 'hidden' ? 'style="visibility:hidden" ' : '').'onChange="javascript:submit()">
							<option value="0">'.JText::_('ALL').'</option>'; 
			$complete .= '<option value="1"';
			if($complete_application == 1) $complete .= ' selected';
			$complete .= '>'.JText::_('YES').'</option>';
			$complete .= '<option value="2"';
			if($complete_application == 2) $complete .= ' selected';
			$complete .= '>'.JText::_('NO').'</option>';
			$complete .= '</select>';
			if ($types['complete'] != 'hidden') $complete .= '</div></div>';
			$filters .= $complete;
		}

		if($params['validate'] !== NULL){
			$validate = '';
			if ($types['validate'] != 'hidden') $validate .= '<div class="em_filters" id="validate">
															  <div class="em_label"><label>'.JText::_('VALIDATED_APPLICATION').'</label></div>
															  <div class="em_filtersElement">';
			$validate .= '<select name="validate" '.($types['validate'] == 'hidden' ? 'style="visibility:hidden" ' : '').'onChange="javascript:submit()">
							<option value="0">'.JText::_('ALL').'</option>'; 
			$validate .= '<option value="1"';
			if($validate_application == 1) $validate .= ' selected';
			$validate .= '>'.JText::_('VALIDATED').'</option>';
			$validate .= '<option value="2"';
			if($validate_application == 2) $validate .= ' selected';
			$validate .= '>'.JText::_('UNVALIDATED').'</option>';
			$validate .= '</select>';
			if ($types['validate'] != 'hidden') $validate .= '</div></div>';
			$filters .= $validate;
		}
		
		//Advance filter builtin
		$elements =& EmundusHelperFilters::getElements();
		$adv_filter = '<div class="em_filters" id="em_adv_filters"><a href="javascript:addElement();"><span class="editlinktip hasTip" title="'.JText::_('NOTE').'::'.JText::_('FILTER_HELP').'">'.JText::_('ELEMENT_FILTER').'</span>';
        $adv_filter .= '<input type="hidden" value="0" id="theValue" />';
        $adv_filter .= '<img src="'.JURI::Base().'media/com_emundus/images/icones/viewmag+_16x16.png" alt="'.JText::_('ADD_SEARCH_ELEMENT').'" id="add_filt"/></a>';
		$adv_filter .= '<div id="myDiv">';
		//var_dump($search);
		if (count($search)>0 && isset($search) && is_array($search)) {
			$i=0;
			$selected_adv = "";
			foreach($search as $sf) {
				$adv_filter .= '<div id="filter'.$i.'">';
				$adv_filter .= '<select name="elements[]" id="elements" onChange="javascript:submit()">
				<option value="">'.JText::_('PLEASE_SELECT').'</option>';  
					$groupe ="";
					$length = 50;
					foreach($elements as $element) { 
						$groupe_tmp = $element->group_label;
						$dot_grp = strlen($groupe_tmp)>=$length?'...':'';
						$dot_elm = strlen($element->element_label)>=$length?'...':'';
						
						if ($groupe != $groupe_tmp) {
							$adv_filter .= '<option class="emundus_search_grp" disabled="disabled" value="">'.substr(strtoupper($groupe_tmp), 0, $length).$dot_grp.'</option>';
							$groupe = $groupe_tmp;
						}
						$adv_filter .= '<option class="emundus_search_elm" value="'.$element->table_name.'.'.$element->element_name.'"';
						if($element->table_name.'.'.$element->element_name == $sf) {
							$adv_filter .= ' selected';
							$selected_adv = $element;
						}
						$adv_filter .= '>'.substr($element->element_label, 0, $length).$dot_elm.'</option>'; 
					}
				$adv_filter .= '</select>';
				if(empty($search_values[$i])) $search_values[$i] = "";
				if($selected_adv != "")
					$adv_filter .= EmundusHelperFilters::setSearchBox($selected_adv, $search_values[$i], "elements_values");
				$adv_filter .= '<a href="javascript:removeElement(\'filter'.$i.'\', 1)"><img src="'.JURI::Base().'media/com_emundus/images/icones/viewmag-_16x16.png" alt="'.JText::_('REMOVE_SEARCH_ELEMENT').'" id="add_filt"/></a>'; 
				$i++; 
				$adv_filter .= '</div>';
			} 
		}//else{
		//echo 'ici';
		//}
        $adv_filter .= '</div></div>';
		$filters .= $adv_filter;

		//Other filters builtin
		if($params['other'] !== NULL && !empty($tables) && $tables[0] != ""){
			$other_elements =& EmundusHelperFilters::getElementsOther($tables);
			$other_filter = '<div class="em_filters" id="em_other_filters"><a href="javascript:addElementOther();"><span class="editlinktip hasTip" title="'.JText::_('NOTE').'::'.JText::_('FILTER_HELP').'">'.JText::_('OTHER_FILTERS').'</span>';
        	$other_filter .= '<input type="hidden" value="0" id="theValue_other" />';
        	$other_filter .= '<img src="'.JURI::Base().'media/com_emundus/images/icones/viewmag+_16x16.png" alt="'.JText::_('ADD_SEARCH_ELEMENT').'" id="add_filt"/></a>';
			$other_filter .= '<div id="otherDiv">';
		
			if (count($search_other)>0 && isset($search_other) && is_array($search_other)) {
				$i=0;
				$selected_other = "";
				foreach($search_other as $sf) {
					$other_filter .= '<div id="filter_other'.$i.'">';
					$other_filter .= '<select name="elements_other[]" id="elements_other" onChange="javascript:submit()">
					<option value="">'.JText::_('PLEASE_SELECT').'</option>';
					$groupe = "";
					$length = 50;
					if(!empty($other_elements))
						foreach($other_elements as $element_other) {
							$groupe_tmp = $element_other->group_label;
							$dot_grp = strlen($groupe_tmp)>=$length?'...':'';
							$dot_elm = strlen($element_other->element_label)>=$length?'...':'';
							if ($groupe != $groupe_tmp) {
								$other_filter .= '<option class="emundus_search_grp" disabled="disabled" value="">'.substr(strtoupper($groupe_tmp), 0, $length).$dot_grp.'</option>';
								$groupe = $groupe_tmp;
							}
						$other_filter .= '<option class="emundus_search_elm_other" value="'.$element_other->table_name.'.'.$element_other->element_name.'"'; // = result_for; engaged; scholarship...
						if($element_other->table_name.'.'.$element_other->element_name == $sf){
							$other_filter .= ' selected';
							$selected_other = $element_other;
						}
						$other_filter .= '>'.substr($element_other->element_label, 0, $length).$dot_elm.'</option>'; 
					}
					$other_filter .= '</select>';
					if(empty($search_values_other[$i])) $search_values_other[$i] = "";
					if ($selected_other != "")
					//var_dump($selected_other);
					echo'<BR />';
					//var_dump($search_values_other[$i]);
						$other_filter .= EmundusHelperFilters::setSearchBox($selected_other, $search_values_other[$i], "elements_values_other");
					$other_filter .= '<a href="javascript:removeElement(\'filter_other'.$i.'\', 2)"><img src="'.JURI::Base().'media/com_emundus/images/icones/viewmag-_16x16.png" alt="'.JText::_('REMOVE_SEARCH_ELEMENT').'" id="add_filt"/></a>';
					$i++; 
					$other_filter .= '</div>';
				} 
			}
			$other_filter .= '</div></div>';
			$filters .= $other_filter;
		}

		$filters .= '<div class="buttons"><input type="submit" name="search_button" onclick="document.pressed=this.name" value="'.JText::_('SEARCH_BTN').'"/>';
		$filters .='<input type="submit" name="clear_button" onclick="document.pressed=this.name" value="'.JText::_('CLEAR_BTN').'"/></div>';
		$filters .= '</fieldset>';
		
		return $filters;
	}
}
?>