<?php
/**
 * Profile Model for eMundus Component
 * 
 * @package    eMundus
 * @subpackage Components
 *             components/com_emundus/emundus.php
 * @link       http://www.decisionpublique.fr
 * @license    GNU/GPL
 */
 
// No direct access
 
defined( '_JEXEC' ) or die( 'Restricted access' );
 
jimport( 'joomla.application.component.model' );
 
class EmundusModelProfile extends JModel
{
	var $_db = null;
	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	function __construct()
	{
		parent::__construct();
		$this->_db =& JFactory::getDBO();
	}

	/**
	* Gets the greeting
	* @return string The greeting to be displayed to the user
	*/
	function getProfile($p)
	{
		$query = 'SELECT * FROM #__emundus_setup_profiles WHERE id='.mysql_real_escape_string($p);
		$this->_db->setQuery( $query );
		return $this->_db->loadObject();
	}
	
	function getAttachments($p)
	{
		$query = 'SELECT attachment.id, attachment.value, profile.id AS selected, profile.displayed, profile.mandatory, profile.bank_needed FROM #__emundus_setup_attachments AS attachment
					LEFT JOIN #__emundus_setup_attachment_profiles AS profile ON profile.attachment_id = attachment.id AND profile.profile_id='.mysql_real_escape_string($p).' ORDER BY attachment.ordering';
		$this->_db->setQuery( $query );
		return $this->_db->loadObjectList();
	}
	
	function getForms($p)
	{
		$query = 'SELECT fbtable.id, fbtable.label, menu.id>0 AS selected, menu.ordering AS `order` FROM #__fabrik_lists AS fbtable 
					LEFT JOIN #__menu AS menu ON fbtable.id = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("listid=",menu.link)+7, 3), "&", 1)
					AND menu.menutype=(SELECT profile.menutype FROM #__emundus_setup_profiles AS profile WHERE profile.id = '.mysql_real_escape_string($p).')
					WHERE fbtable.created_by_alias = "form" ORDER BY selected DESC, menu.ordering ASC, fbtable.label ASC';
		$this->_db->setQuery( $query );
		return $this->_db->loadObjectList();
	}
}
?>