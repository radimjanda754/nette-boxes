<?php

namespace App\Model;

use App\Utils\RandomStringGenerator;
use Nette;
use Tracy\Debugger;

class BoxesManager extends BasicManager
{
	use Nette\SmartObject;

	/**
	 * @var UnlocksManager
	 */
	private $unlocksManager;


	public function __construct(Nette\Database\Context $database, UnlocksManager $unlocksManager)
	{
		$this->database = $database;
		$this->unlocksManager = $unlocksManager;
		$this->table = $this->database->table('bx_boxes');
	}

	public function getByCode($code) : Nette\Database\Table\Selection {
		return $this->table->where('code = ?',$code);
	}

	public function getBoxesForUser($user) {
		return $this->table->where("owner_id = ?",$user);
	}

	public function assignBox($loginId, $boxCode) {
		$boxCode = strtolower($boxCode);
		$row = $this->table->where("code = ?",$boxCode)->fetch();
		if(!$row) {
			throw new Nette\Neon\Exception("Chyba! Krabička s požadovaným kódem nebyla nalezena. Zkuste to znovu.");
		}
		if($row->offsetGet('owner_id')) {
			throw new Nette\Neon\Exception("Chyba! Krabička s požadovaným kódem již je přiřazena k jinému účtu.");
		}
		$row['owner_id']=$loginId;
		$this->table->update($row);
	}

	public function insertBox($data) {
		if(!$data["code"])
			$data["code"] = $this->generateBoxKey();
		else if($data["code"]) {
			if(!$this->checkBoxKey(strtolower($data["code"]))) {
				throw new Nette\Neon\Exception("Kód je ve špatném formátu. Formát musí odpovídat: box-XXXX-XXXX-XXXX");
			}
		}
		try {
			$row = $this->insert($data);
			$this->unlocksManager->insert(['box_id' => $row->id]);
			return $row;
		} catch (Nette\Database\UniqueConstraintViolationException $ex) {
			throw new Nette\Neon\Exception("Kód kód musí být jedinečný.");
		}
	}

	public function getBoxesDescriptionForUser($userId) {
		$output = array();
		$output['values'] = array();
		$output['ids'] = array();
		foreach($this->table->where('owner_id = ?',$userId)->fetchAll() as $row) {
			$output['values'][$row->id] = $row->code;
			$output['ids'][]=$row->id;
		}
		return $output;
	}

	public function generateBoxKey() {
		return 'box-'.RandomStringGenerator::generate()."-".RandomStringGenerator::generate();
	}

	public function checkBoxKey($key) {
		if(strlen($key)>0 && preg_match("/^box-[0-9a-z]{4}-[0-9a-z]{4}$/",$key))
			return true;
		return false;
	}
}