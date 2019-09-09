<?php

namespace App\Model;

use App\Exceptions\NullNameException;
use App\Utils\RandomStringGenerator;
use Nette;
use Tracy\Debugger;

class LoginkeysManager extends BasicManager
{
	use Nette\SmartObject;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
		$this->table = $this->database->table('bx_login_keys');
	}

	public function insertLogin($data) {
		if(!$data["code"])
			$data["code"]=$this->generateLoginKey();
		else if($data["code"]) {
			if(!$this->checkLoginKey(strtolower($data["code"]))) {
				throw new Nette\Neon\Exception("Kód je ve špatném formátu. Formát musí odpovídat: login-XXXX-XXXX-XXXX");
			}
		}
		try {
			return $this->insert($data);
		} catch (Nette\Database\UniqueConstraintViolationException $ex) {
			throw new Nette\Neon\Exception("Kód kód musí být jedinečný.");
		}
	}

	public function getByCode($code) : Nette\Database\Table\Selection {
		return $this->table->where("code = ?",$code);
	}

	public function generateLoginKey() {
		return 'login-'.RandomStringGenerator::generate()."-".RandomStringGenerator::generate();
	}

	public function checkLoginKey($key) {
		if(strlen($key)>0 && preg_match("/^login-[0-9a-z]{4}-[0-9a-z]{4}$/",$key))
			return true;
		return false;
	}
}