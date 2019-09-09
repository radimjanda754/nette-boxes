<?php

namespace App\Presenters;

use App\Model\BoxesManager;
use App\Model\UnlocksManager;
use Nette;


class ApiPresenter extends Nette\Application\UI\Presenter
{
	/**
	 * @var Nette\Http\Request
	 */
	private $httpRequest;

	/**
	 * @var BoxesManager
	 */
	private $boxesManager;

	/**
	 * @var UnlocksManager
	 */
	private $unlocksManager;

	/**
	 * ApiPresenter constructor.
	 * @param Nette\Http\Request $httpRequest
	 * @param BoxesManager $boxesManager
	 * @param UnlocksManager $unlocksManager
	 */
	public function __construct(Nette\Http\Request $httpRequest, BoxesManager $boxesManager, UnlocksManager $unlocksManager)
	{
		$this->httpRequest = $httpRequest;
		$this->boxesManager = $boxesManager;
		$this->unlocksManager = $unlocksManager;
	}


	public function actionDefault()
	{
		$boxId = $this->httpRequest->getHeader('X-Numbers-BoxId');
		$outputArray2 = array();

		if(!$boxId) {
			$outputArray2[]=['error' => 1001,'errorText' => 'Špatný požadavek na server.'];
			$this->sendJson($outputArray2);
		}

		$boxesSelection = $this->boxesManager->getByCode($boxId)->fetch();
		if(!$boxesSelection) {
			$outputArray2[]=['error' => 1002 ,'errorText' => 'Krabice s daným kódem nebyla nalezena. Zkontrolujte kód a zkuste to znovu.'];
			$this->sendJson($outputArray2);
		}

		$unlock=$this->unlocksManager->getFirstByParams('box_id = ?',$boxesSelection->offsetGet('id'));
		if(!$unlock) {
			$outputArray2[] = ['error' => 1003 ,'errorText' => 'Krabice s daným kódem má špatně nastavené odemikání. Je nutné ho v administraci opravit.'];
			$this->sendJson($outputArray2);
		}

		if(!$this->unlocksManager->validateUnlock($unlock)) {
			$outputArray2[] = ['error' => 1004 ,'errorText' => 'Krabice s daným kódem má špatně nastavené odemikání. Je nutné ho v administraci opravit.'];
			$this->sendJson($outputArray2);
		}

		$output = array();
		if(strlen($unlock['lat']>0) && strlen($unlock['lon']>0)) {
			$output['lat'] = $unlock['lat'];
			$output['lon'] = $unlock['lon'];
		}
		if(strlen($unlock['time']>0)) {
			$date = date_create_from_format('Y-m-d H:i', $unlock['time']);
			$output['time'] = $date->getTimestamp()-time();
		}
		$output['k1'] = $boxesSelection['k1'];
		$output['k2'] = $boxesSelection['k2'];
		$output['k3'] = $boxesSelection['k3'];
		$output['story'] = $unlock['story'];


		$outputArray2[] = $output;
		$this->sendJson($outputArray2);
	}

}
