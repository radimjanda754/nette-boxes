<?php

namespace App\Presenters;

use App\Model\BoxesManager;
use App\Model\LoginkeysManager;
use App\Model\UnlocksManager;
use Nette;
use Tracy\Debugger;
use Ublaboo\DataGrid\DataGrid;


class BoxesPresenter extends Nette\Application\UI\Presenter
{
	/**
	 * @var LoginkeysManager
	 */
	private $loginKeysManager;

	/**
	 * @var BoxesManager
	 */
	private $boxesManager;

	/**
	 * @var UnlocksManager
	 */
	private $unlocksManager;

	private $iamAdmin=false;

	private $currentBoxes;

	/**
	 * BoxesPresenter constructor.
	 * @param LoginkeysManager $loginKeysManager
	 * @param BoxesManager $boxesManager
	 * @param UnlocksManager $unlocksManager
	 */
	public function __construct(LoginkeysManager $loginKeysManager, BoxesManager $boxesManager, UnlocksManager $unlocksManager)
	{
		$this->loginKeysManager = $loginKeysManager;
		$this->boxesManager = $boxesManager;
		$this->unlocksManager = $unlocksManager;
	}

	public function beforeRender()
	{
		if(!$this->user->isLoggedIn()) {
			$this->redirect('Homepage:Default');
		}
		if($this->user->isInRole('admin')) {
			$this->iamAdmin = true;
		}
		$this->template->iamAdmin = $this->iamAdmin;
		$this->currentBoxes = $this->boxesManager->getBoxesDescriptionForUser($this->user->getId());
	}

	protected function createComponentSimpleAddBoxForm()
	{
		$form = new Nette\Application\UI\Form();
		$form->addText('code', 'Kód krabičky:')->setAttribute("placeholder","box-XXXX-XXXX")->setRequired("Vyplntě kód");
		$form->addSubmit('submit', 'Přidat');
		$form->onSuccess[] = function($form) {
			$values = $form->getValues();

			if($row=$this->boxesManager->getByCode($values['code'])->fetch()) {
				if($row->owner_id) {
					$this->flashMessage('Krabička již je přiřazena k jinému účtu. Kontaktujte administrátora.', 'error');
					$this->redirect('Boxes:Default');
				} else {
					$row->update(['owner_id' => $this->user->getId()]);
					$this->flashMessage('Krabička byla přidána k vašemu účtu.', 'success');
					$this->redirect('Boxes:Default');
				}
			} else {
				$this->flashMessage('Špatně zadaný tajný přihlašovací kód. Zkuste to znovu', 'error');
				$this->redirect('Boxes:Default');
			}
		};
		return $form;
	}

	public function handlelogout() {
		$this->user->logout();
	}

	protected function createComponentBoxGrid() : DataGrid {
		$datagrid = new DataGrid();

		$datagrid->setDataSource($this->boxesManager->getBoxesForUser($this->user->getId()));
		$datagrid->setItemsPerPageList([5, 10, 25]);

		/**
		 * Big inline editing
		 */
		$datagrid->addInlineEdit()
			->onControlAdd[] = function($container) {
			$container->addText('k1', '')->setAttribute("min", 0)->setAttribute("max", 9)->setAttribute("type", "number");
			$container->addText('k2', '')->setAttribute("min", 0)->setAttribute("max", 9)->setAttribute("type", "number");
			$container->addText('k3', '')->setAttribute("min", 0)->setAttribute("max", 9)->setAttribute("type", "number");
			$container->addText('note', '');
		};

		$datagrid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
			$container->setDefaults([
				'k1' => $item['k1'],
				'k2' => $item['k2'],
				'k3' => $item['k3'],
				'note' => $item['note'],
			]);
		};

		$datagrid->getInlineEdit()->onSubmit[] = function($id, $values) {
			try {
				$this->boxesManager->update($id,$values);
				$this->flashMessage('Data byla aktualizována', 'success');
				$this->redirect('Boxes:Default');
			} catch (Nette\Neon\Exception $ex) {
				$this->flashMessage($ex->getMessage(), 'error');
				$this->redirect('Boxes:Default');
			}
		};


		/**
		 * Columns
		 */
		$datagrid->addColumnText('code', 'Kód');
		$datagrid->addColumnText('note', 'Poznámka');
		$datagrid->addColumnText('k1', 'K1');
		$datagrid->addColumnText('k2', 'K2');
		$datagrid->addColumnText('k3', 'K3');


		return $datagrid;
	}

	protected function createComponentUnlockGrid() : DataGrid {
		$datagrid = new DataGrid();

		$this->currentBoxes = $this->boxesManager->getBoxesDescriptionForUser($this->user->getId());
		$datagrid->setDataSource($this->unlocksManager->getUnlocksForBoxes($this->currentBoxes));
		$datagrid->setItemsPerPageList([5, 10, 25]);

		/**
		 * Big inline editing
		 */
		$datagrid->addInlineEdit()
			->onControlAdd[] = function($container) {
			$container->addTextArea('story', '')->setAttribute("style","height:75px;");
			$container->addTextArea('address', '')->setAttribute("style","height:75px;");
			$container->addText('lat', '')->setAttribute("type", "number")->setAttribute('step','0.000001');
			$container->addText('lon', '')->setAttribute("type", "number")->setAttribute('step','0.000001');
			$container->addText('time', '')
				->setAttribute('data-provide', 'datetimepicker')
				->setAttribute('data-date-format', 'yyyy-mm-dd hh:ii');
		};

		$datagrid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
			$container->setDefaults([
				'story' => $item['story'],
				'address' =>  $item['address'],
				'lat' => $item['lat'],
				'lon' => $item['lon'],
				'time' => $item['time']
			]);
		};

		$datagrid->getInlineEdit()->onSubmit[] = function($id, $values) {
			try {
				//$values['box_id']=$values['box_id_text'];
				unset($values['box_id_text']);
				$this->unlocksManager->updateUnlock($id,$values);
				$this->flashMessage('Odemknutí upraveno', 'success');
				$this->redirect('Boxes:Default');
			} catch (Nette\Neon\Exception $ex) {
				$this->flashMessage($ex->getMessage(), 'error');
				$this->redirect('Boxes:Default');
			}
		};

		/**
		 * Columns
		 */

		$datagrid->addColumnStatus('box_id_text', 'Krabička');
		$datagrid->addColumnText('story', 'Příběh');
		$datagrid->addColumnText('address', 'Adresa');
		$datagrid->addColumnText('lat', 'Lat');
		$datagrid->addColumnText('lon', 'Lon');
		$datagrid->addColumnText('time', 'Čas otevření');

		return $datagrid;
	}
}
