<?php
/**
 * Created by PhpStorm.
 * User: Radim
 * Date: 02.08.2018
 * Time: 19:37
 */

namespace App\Model;


use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\SmartObject;

class BasicManager
{
	use SmartObject;

	/**
	 * @var Context
	 */
	protected $database;

	/**
	 * @var Selection
	 */
	protected $table;

	public function getAll() : Selection {
		return $this->table;
	}

	public function getById($id) : ActiveRow {
		return $this->table->where("id = ?",$id)->fetch();
	}

	public function getFirstByParams($condition, $params) : ActiveRow {
		return $this->table->where($condition,$params)->fetch();
	}

	public function delete($id) {
		$this->table->where("id = ?",$id)->delete();
	}

	public function update($id,$values) {
		$this->table->where('id = ?',$id)->update($values);
	}

	public function insert($values) : ActiveRow {
		return $this->table->insert($values);
	}
}