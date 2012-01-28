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
		$status = $this->twitter->request('POST', $this->twitter->url('oauth/request_token', ''), array(
			'oauth_callback' => $redirect,
		));
		
		if($status === 200)
		{
			$_SESSION['oauth'] = $this->twitter->extract_params($this->twitter->response['response']);
			
			$this->EE->functions->redirect($this->twitter->url('oauth/authorize', '') . "?oauth_token={$_SESSION['oauth']['oauth_token']}");
		}
		
		// TODO: fail here
	}
	
	/**
	 * Finish the registration process
	 */
	public function register_finish()
	{
		if( ! $this->EE->input->get_post('oauth_verifier'))
		{
			return FALSE;
		}
		
		// add oauth tokens to sdk
		$this->twitter->config['user_token'] = $_SESSION['oauth']['oauth_token'];
		$this->twitter->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];
		
		// request access token
		$status = $this->twitter->request('POST', $this->twitter->url('oauth/access_token', ''), array(
			'oauth_verifier' => $this->EE->input->get_post('oauth_verifier'),
		));
		
		if($status === 200)
		{
			$_SESSION['access_token'] = $this->twitter->extract_params($this->twitter->response['response']);
			
			unset($_SESSION['oauth']);
			
			// reconfigure the sdk with new tokens
			$this->twitter->config['user_token'] = $_SESSION['access_token']['oauth_token'];
			$this->twitter->config['user_secret'] = $_SESSION['access_token']['oauth_token_secret'];
			
			// get user info
			$status = $this->twitter->request('GET', $this->twitter->url('1/account/verify_credentials'));
			
			if($status === 200)
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
				
				$_SESSION['access_token'];
				
				return $data;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Login
	 *
	 * @param	string
	 */
	public function login($redirect)
	{
		// skip a lot of this mess if we already have an access token
		if( ! empty($_SESSION['access_token']))
		{
			goto verify_credentials;
		}
		
		// get request token if we haven't already
		if(empty($_SESSION['oauth']))
		{
			// get a request token
			$status = $this->twitter->request('POST', $this->twitter->url('oauth/request_token', ''), array(
				'oauth_callback' => $this->EE->functions->create_url($this->EE->uri->uri_string()),
			));
			
			if($status === 200)
			{
				$_SESSION['oauth'] = $this->twitter->extract_params($this->twitter->response['response']);
				
				// redirect for authentication
				$this->EE->functions->redirect($this->twitter->url('oauth/authenticate', '') . "?oauth_token={$_SESSION['oauth']['oauth_token']}");
			}
		}
		
		// if request was denied, fail
		if( ! $this->EE->input->get_post('oauth_verifier'))
		{
			unset($_SESSION['oauth']);
			
			return FALSE;
		}
		
		// add oauth tokens to sdk
		$this->twitter->config['user_token'] = $_SESSION['oauth']['oauth_token'];
		$this->twitter->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];
		
		// request access token
		$status = $this->twitter->request('POST', $this->twitter->url('oauth/access_token', ''), array(
			'oauth_verifier' => $this->EE->input->get_post('oauth_verifier'),
		));
		
		if($status === 200)
		{
			$_SESSION['access_token'] = $this->twitter->extract_params($this->twitter->response['response']);
			
			// get the user's credentials and return the id
			verify_credentials:
			$this->twitter->config['user_token'] = $_SESSION['access_token']['oauth_token'];
			$this->twitter->config['user_secret'] = $_SESSION['access_token']['oauth_token_secret'];
			
			$status = $this->twitter->request('GET', $this->twitter->url('1/account/verify_credentials'));
			
			if($status === 200)
			{
				$user = json_decode($this->twitter->response['response']);
				
				return $user->id_str;
			}
		}
		
		return FALSE;
	}
	
}