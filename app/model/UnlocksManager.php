<?php

namespace App\Model;

use App\Exceptions\NullNameException;
use Nette;
use Tracy\Debugger;

class UnlocksManager extends BasicManager
{
	use Nette\SmartObject;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
		$this->table = $this->database->table('bx_unlocks');
	}

	public function getUnlocksForBoxes($boxes){
		$data = array();
		foreach ($this->table->where('box_id IN ?',$boxes['ids']) as $row) {
			$data[] = [
				'id' => $row->id,
				'box_id' => $row->box_id,
				'box_id_text' => $boxes['values'][$row->box_id],
				'address' => $row->address,
				'story' => $row->story,
				'lat' => $row->lat,
				'lon' => $row->lon,
				'time' => $row->time,
			];
		}
		return $data;
	}

	public function updateUnlock($id,$values) {
		$this->updateLatLon($values);
		$this->update($id,$values);
	}

	public function insertUnlock($values) {
		if(strlen($values['box_id']) == 0){
			throw new Nette\Neon\Exception('Chyba. Pole - Krabička nemůže být prázdné.');
		}
		//$this->updateLatLon($values);
		$this->insert($values);
	}

	private function updateLatLon($values) {
		if(strlen($values['lat'])>0 ||strlen($values['lon'])>0 || strlen($values['address'])>0) {
			if (strlen($values['lat']) == 0 && strlen($values['lon']) == 0 && strlen($values['address']) > 0) {
				$address = $values['address']; // Google HQ
				$prepAddr = str_replace(' ', '+', $address);
				$geocode = file_get_contents('https://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');
				$output = json_decode($geocode);
				$values['lat'] = $output->results[0]->geometry->location->lat;
				$values['lon'] = $output->results[0]->geometry->location->lng;
			}
			if (strlen($values['lat']) == 0 || strlen($values['lon']) == 0) {
				throw new Nette\Neon\Exception('Chyba! Lat/Lon se nepodařilo nastavit. Nastavte Lat a Lon souřadnice nebo zadejte správnou adresu.');
			}
		} else {
			$values['lat'] = null;
			$values['lon'] = null;
			$values['address'] = null;
			if(strlen($values['time']) == 0){
				throw new Nette\Neon\Exception('Chyba! Je nutné nastavit buď lokaci nebo čas pro odemknutí (případně obojí).');
			}
			if(!$this->validateDate($values['time']))
				throw new Nette\Neon\Exception('Chyba! Datum je ve špatném formátu. Správny formát: YYYY-MM-DD hh:ss');
		}

		if(strlen($values['time']) > 0){
			if(!$this->validateDate($values['time']))
				throw new Nette\Neon\Exception('Chyba! Datum je ve špatném formátu. Správny formát: YYYY-MM-DD hh:ss');
		}
		return true;
	}

	public function validateUnlock($values){
		if(strlen($values['lat'])>0 && strlen($values['lon'])>0) {
			return true;
		}
		if(strlen($values['time'])>0) {
			return true;
		}
		return false;
	}

	public function validateDate($date, $format = 'Y-m-d H:i')
	{
		$d = \DateTime::createFromFormat($format, $date);
		// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
		return $d && $d->format($format) === $date;
	}
}