<?php
/**
 * Created by PhpStorm.
 * User: Radim
 * Date: 02.08.2018
 * Time: 17:30
 */

class SimpleAuthenticator implements \Nette\Security\IAuthenticator
{
	/**
	 * @var \App\Model\LoginkeysManager
	 */
	private $loginkeysManager;

	/**
	 * SimpleAuthenticator constructor.
	 * @param \App\Model\LoginkeysManager $LoginkeysManager
	 */
	public function __construct(\App\Model\LoginkeysManager $loginkeysManager)
	{
		$this->loginkeysManager = $loginkeysManager;
	}


	function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
		$row = $this->loginkeysManager->getByCode($password)->fetch();
		if (!$row) {
			throw new \Nette\Security\AuthenticationException('User not found.');
		}

		$roles = 'user';
		if (strpos($password, 'admin') !== false) {
			$roles = 'admin';
		}

		return new \Nette\Security\Identity($row->id, $roles, ['username' => $username]);
	}
}