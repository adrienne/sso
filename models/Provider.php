<?php

abstract class Provider {
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	/**
	 * Start registration process
	 */
	abstract public function register_start($redirect);
	
	/**
	 * Finish registration process
	 */
	abstract public function register_finish();
	
	/**
	 * Login
	 */
	abstract public function login();
	
}