<?php

abstract class Provider {
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	abstract public function register_start($redirect);
	
	abstract public function register_finish();
	
	abstract public function login($redirect);
	
}