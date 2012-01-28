<?php

/**
 * Social Sign On Module Install/Update File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		eecoder
 * @link		http://eecoder.com/
 */
class Sso_upd {
	
	/**
	 * @var	string
	 */
	public $version = '1.0';
	
	/**
	 * @var	object
	 */
	private $EE;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	/**
	 * Installation Method
	 *
	 * @return	bool
	 */
	public function install()
	{
		$mod_data = array(
			'module_name' => 'Sso',
			'module_version' => $this->version,
			'has_cp_backend' => 'n',
			'has_publish_fields' => 'n',
		);
		
		$this->EE->db->insert('modules', $mod_data);
		
		$sql[] = <<<SQL
CREATE TABLE `exp_sso_accounts` (
	`sso_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`provider` varchar(20) NOT NULL DEFAULT '',
	`user_id` varchar(50) NOT NULL,
	`data` text NOT NULL,
	`member_id` int(10) DEFAULT NULL,
	PRIMARY KEY (`sso_id`),
	KEY `member_id` (`member_id`),
	KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL;
		
		foreach($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
		return TRUE;
	}
	
	/**
	 * Uninstall
	 *
	 * @return	bool
	 */	
	public function uninstall()
	{
		$mod_id = $this->EE->db->select('module_id')->get_where('modules', array(
			'module_name' => 'Sso'
		))->row('module_id');
		
		$this->EE->db->where('module_id', $mod_id)->delete('module_member_groups');
		
		$this->EE->db->where('module_name', 'Sso')->delete('modules');
		
		$this->EE->db->query('DROP TABLE exp_sso_accounts');
		
		return TRUE;
	}
	
	/**
	 * Module Updater
	 *
	 * @return	bool
	 */	
	public function update($current = '')
	{
		return TRUE;
	}
	
}