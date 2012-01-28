<?php

require_once __DIR__.'/../libraries/twitter/tmhOAuth.php';

class Twitter_provider extends Provider {
	
	/**
	 * @var	string
	 */
	const CONSUMER_KEY = 't06FVvkxmELax1SzRZrLSg';
	
	/**
	 * @var	string
	 */
	const CONSUMER_SECRET = 'bAneplyrXeiYfC0O7AckGAe7foyKCiIbPxdPiLriN4';
	
	/**
	 * @var	object
	 */
	private $twitter;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		// setup sdk
		$this->twitter = new tmhOAuth(array(
			'consumer_key' => self::CONSUMER_KEY,
			'consumer_secret' => self::CONSUMER_SECRET,
		));
	}
	
	/**
	 * Start the registration process
	 *
	 * @param	string
	 */
	public function register_start($redirect)
	{
		$success = $this->get_request_token($redirect);
		
		if($success === TRUE)
		{
			$this->EE->functions->redirect($this->twitter->url('oauth/authorize', '') . "?oauth_token={$_SESSION['oauth']['oauth_token']}");
		}
		
		// TODO: make this better
		die('Could not get access token!');
	}
	
	/**
	 * Finish the registration process
	 *
	 * @return	array|bool
	 */
	public function register_finish()
	{
		if( ! $this->EE->input->get_post('oauth_verifier'))
		{
			return FALSE;
		}
		
		// request access token
		$success = $this->get_access_token($this->EE->input->get_post('oauth_verifier'));
		
		if($success === TRUE)
		{
			// get user info
			$code = $this->twitter->request('GET', $this->twitter->url('1/account/verify_credentials'));
			
			if($code === 200)
			{
				$user = json_decode($this->twitter->response['response']);
				
				$profile = json_encode(array(
					'first-name' => '',
					'middle-initial' => '',
					'last-name' => '',
					'employer' => '',
					'email' => '',
					'phone' => '',
					'address' => '',
					'address2' => '',
					'city' => '',
					'state-province' => '',
					'postal-code' => '',
					'country' => '',
					'gender' => '',
					'bday_m' => '',
					'bday_d' => '',
					'bday_y' => '',
					'occupation' => '',
					'username' => $user->screen_name,
				));
				
				$data = array(
					'user_id' => $user->id_str,
					'data' => $profile,
				);
				
				return $data;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Login
	 *
	 * @param	string
	 * @return	string|bool
	 */
	public function login()
	{
		// skip a lot of this mess if we already have an access token
		if( ! empty($_SESSION['access_token']))
		{
			$this->twitter->config['user_token'] = $_SESSION['access_token']['oauth_token'];
			$this->twitter->config['user_secret'] = $_SESSION['access_token']['oauth_token_secret'];
			
			goto verify_credentials;
		}
		
		// get request token if we haven't already
		if(empty($_SESSION['oauth']))
		{
			$redirect = $this->EE->functions->create_url($this->EE->uri->uri_string());
			
			$success = $this->get_request_token($redirect);
			
			if($success === TRUE)
			{
				$this->EE->functions->redirect($this->twitter->url('oauth/authenticate', '') . "?oauth_token={$_SESSION['oauth']['oauth_token']}");
			}
		}
		
		// if request was denied, fail
		if( ! $this->EE->input->get_post('oauth_verifier'))
		{
			unset($_SESSION['oauth']);
			
			return FALSE;
		}
		
		$success = $this->get_access_token($this->EE->input->get_post('oauth_verifier'));
		
		if($success === TRUE)
		{
			// get the user's credentials and return the id
			verify_credentials:
			$code = $this->twitter->request('GET', $this->twitter->url('1/account/verify_credentials'));
			
			if($code === 200)
			{
				$user = json_decode($this->twitter->response['response']);
				
				return $user->id_str;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Get a request token from Twitter
	 *
	 * @param	string
	 * @return	bool
	 */
	private function get_request_token($redirect)
	{
		$code = $this->twitter->request('POST', $this->twitter->url('oauth/request_token', ''), array(
			'oauth_callback' => $redirect,
		));
		
		// set the session info
		if($code === 200)
		{
			$_SESSION['oauth'] = $this->twitter->extract_params($this->twitter->response['response']);
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Get an access token from Twitter
	 *
	 * @param	string
	 * @return	bool
	 */
	private function get_access_token($verifier)
	{
		// add request tokens to sdk
		$this->twitter->config['user_token'] = $_SESSION['oauth']['oauth_token'];
		$this->twitter->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];
		
		// get the access token
		$code = $this->twitter->request('POST', $this->twitter->url('oauth/access_token', ''), array(
			'oauth_verifier' => $verifier,
		));
		
		if($code === 200)
		{
			$_SESSION['access_token'] = $this->twitter->extract_params($this->twitter->response['response']);
			
			unset($_SESSION['oauth']);
			
			// reconfigure the sdk with new tokens
			$this->twitter->config['user_token'] = $_SESSION['access_token']['oauth_token'];
			$this->twitter->config['user_secret'] = $_SESSION['access_token']['oauth_token_secret'];
			
			return TRUE;
		}
		
		return FALSE;
	}
	
}