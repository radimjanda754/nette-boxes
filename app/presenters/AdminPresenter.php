<?php

namespace App\Presenters;

use App\Model\BoxesManager;
use App\Model\LoginkeysManager;
use App\Model\UnlocksManager;
use Nette;
use Tracy\Debugger;
use Ublaboo\DataGrid\DataGrid;


class AdminPresenter extends Nette\Application\UI\Presenter
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
		if(!$this->user->isInRole('admin')){
			$this->redirect('Boxes:Default');
		}
	}

	public function handlelogout() {
		$this->user->logout();
	}

	protected function createComponentBoxGrid() : DataGrid {
		$datagrid = new DataGrid();

		$datagrid->setDataSource($this->boxesManager->getAll());
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
			$container->addText('code', '');
			$container->addText('owner_id', '')->setAttribute("type", "number");
		};

		$datagrid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
			$container->setDefaults([
				'k1' => $item['k1'],
				'k2' => $item['k2'],
				'k3' => $item['k3'],
				'note' => $item['note'],
				'code' => $item['code'],
				'owner_id' => $item['owner_id']
			]);
		};

		$datagrid->getInlineEdit()->onSubmit[] = function($id, $values) {
			if(strlen($values['owner_id'] == 0))
				$values['owner_id'] = null;
			try {
				$this->boxesManager->update($id,$values);
				$this->flashMessage('Data byla aktualizována', 'success');
				$this->redirect('Admin:Default');
			} catch (Nette\Neon\Exception $ex) {
				$this->flashMessage($ex->getMessage(), 'error');
				$this->redirect('Admin:Default');
			}
		};

		$datagrid->addInlineAdd()
			->onControlAdd[] = function ($container) {
			$container->addText('k1', '')->setAttribute("min", 0)->setAttribute("max", 9)->setAttribute("type", "number");
			$container->addText('k2', '')->setAttribute("min", 0)->setAttribute("max", 9)->setAttribute("type", "number");
			$container->addText('k3', '')->setAttribute("min", 0)->setAttribute("max", 9)->setAttribute("type", "number");
			$container->addText('note', '');
			$container->addText('code', '')->setAttribute("placeholder","Automatické vygenerování");
			$container->addText('owner_id', '')->setAttribute("type", "number");
		};

		$datagrid->getInlineAdd()->onSubmit[] = function ($values) {
			if(strlen($values['owner_id'] == 0))
				$values['owner_id'] = null;
			try {
				$row=$this->boxesManager->insertBox($values);
				$this->flashMessage('Krabička přidána - '.$row->code, 'success');
				$this->redirect('Admin:Default');
			} catch (\Nette\Neon\Exception $ex) {
				$this->flashMessage($ex->getMessage(), 'error');
				$this->redirect('Admin:Default');
			}
		};




		/**
		 * Columns
		 */
		$datagrid->addColumnText('id', 'Id')
			->addAttributes(["width" => "6%"])
			->setSortable()
			->setAlign('left');
		$datagrid->addColumnText('code', 'Kód');
		$datagrid->addColumnText('owner_id', 'Id Majitele');
		$datagrid->addColumnText('note', 'Poznámka');
		$datagrid->addColumnText('k1', 'K1');
		$datagrid->addColumnText('k2', 'K2');
		$datagrid->addColumnText('k3', 'K3');


		/**
		 * Filters
		 */
		$datagrid->addFilterText('id', 'Search', ['id']);
		$datagrid->addFilterText('code', 'Search', ['code']);
		$datagrid->addFilterText('owner_id', 'Search', ['owner_id']);
		$datagrid->addFilterText('note', 'Search', ['note']);


		/**
		 * Actions
		 */

		$datagrid->addAction('delete', '', 'delete!')
			->setIcon('trash')
			->setTitle('Delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm('Odstranit %s?', 'code');


		return $datagrid;
	}

	protected function createComponentUnlockGrid() : DataGrid {
		$datagrid = new DataGrid();

		$datagrid->setDataSource($this->unlocksManager->getAll());
		$datagrid->setItemsPerPageList([5, 10, 25]);

		/**
		 * Big inline editing
		 */
		$datagrid->addInlineEdit()
			->onControlAdd[] = function($container) {
			$container->addText('box_id', '')->setAttribute("type", "number");
			$container->addTextArea('story', '')->setAttribute("style","height:75px;");
			$container->addTextArea('address', '')->setAttribute("style","height:75px;");
			$container->addText('lat', '')->setAttribute("type", "number")->setAttribute('step','0.000001');
			$container->addText('lon', '')->setAttribute("type", "number")->setAttribute('step','0.000001');
			$container->addText('time', '')
				->setAttribute('data-provide', 'datetimepicker')
				->setAttribute('data-date-format', 'yyyy-mm-dd hh:ii');;
		};

		$datagrid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
			$container->setDefaults([
				'box_id' => $item['box_id'],
				'story' => $item['story'],
				'address' =>  $item['address'],
				'lat' => $item['lat'],
				'lon' => $item['lon'],
				'time' => $item['time']
			]);
		};

		$datagrid->getInlineEdit()->onSubmit[] = function($id, $values) {
			try {
				$this->unlocksManager->updateUnlock($id,$values);
				$this->flashMessage('Odemknutí upraveno', 'success');
				$this->redirect('Admin:Default');
			} catch (Nette\Neon\Exception $ex) {
				$this->flashMessage($ex->getMessage(), 'error');
				$this->redirect('Admin:Default');
			}
		};

		$datagrid->addInlineAdd()
			->onControlAdd[] = function ($container) {
			$container->addText('box_id', '')->setAttribute("type", "number");
			$container->addTextArea('story', '')->setAttribute("style","height:75px;");
			$container->addTextArea('address', '')->setAttribute("style","height:75px;");
			$container->addText('lat', '')->setAttribute("type", "number")->setAttribute('step','0.000001');
			$container->addText('lon', '')->setAttribute("type", "number")->setAttribute('step','0.000001');
			$container->addText('time', '')
				->setAttribute('data-provide', 'datetimepicker')
				->setAttribute('data-date-format', 'yyyy-mm-dd hh:ii');;
		};

		$datagrid->getInlineAdd()->onSubmit[] = function ($values) {
			try {
				$this->unlocksManager->insertUnlock($values);
				$this->flashMessage('Odemknutí nastaveno', 'success');
				$this->redirect('Admin:Default');
			} catch (Nette\Neon\Exception $ex) {
				$this->flashMessage($ex->getMessage(), 'error');
				$this->redirect('Admin:Default');
			}
		};

		/**
		 * Columns
		 */
		$datagrid->addColumnText('id', 'Id')
			->addAttributes(["width" => "6%"])
			->setSortable()
			->setAlign('left');
		$datagrid->addColumnText('box_id', 'Id Krabice');
		$datagrid->addColumnText('story', 'Příběh');
		$datagrid->addColumnText('address', 'Adresa');
		$datagrid->addColumnText('lat', 'Lat');
		$datagrid->addColumnText('lon', 'Lon');
		$datagrid->addColumnText('time', 'Čas otevření');


		/**
		 * Filters
		 */
		$datagrid->addFilterText('id', 'Search', ['id']);
		$datagrid->addFilterText('box_id', 'Search', ['box_id']);
		$datagrid->addFilterText('story', 'Search', ['story']);
		$datagrid->addFilterText('address', 'Search', ['address']);
		$datagrid->addFilterText('lat', 'Search', ['lat']);
		$datagrid->addFilterText('lon', 'Search', ['lon']);
		$datagrid->addFilterText('time', 'Search', ['time']);


		/**
		 * Actions
		 */
		$datagrid->addAction('delete', '', 'deleteUnlock!')
			->setIcon('trash')
			->setTitle('Delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm('Odstranit %s?', 'id');


		return $datagrid;
	}

	protected function createComponentLoginGrid() : DataGrid{
		$datagrid = new DataGrid();

		$datagrid->setDataSource($this->loginKeysManager->getAll());
		$datagrid->setItemsPerPageList([5, 10, 25]);

		/**
		 * Big inline editing
		 */
		$datagrid->addInlineEdit()
			->onControlAdd[] = function($container) {
			$container->addText('code', '');
			$container->addText('note', '');
		};

		$datagrid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
			$container->setDefaults([
				'code' => $item['code'],
				'note' => $item['note']
			]);
		};

		$datagrid->getInlineEdit()->onSubmit[] = function($id, $values) {
			try {
				$this->loginKeysManager->update($id,$values);
				$this->flashMessage('Login upraven', 'success');
				$this->redirect('Admin:Default');
			} catch (Nette\Neon\Exception $ex) {
				$this->flashMessage($ex->getMessage(), 'error');
				$this->redirect('Admin:Default');
			}
		};

		$datagrid->addInlineAdd()
			->onControlAdd[] = function ($container) {
			$container->addText('code', '')->setAttribute("placeholder","Automatické vygenerování");
			$container->addText('note', '');
		};

		$datagrid->getInlineAdd()->onSubmit[] = function ($values) {
			try {
				$row = $this->loginKeysManager->insertLogin($values);
				$this->flashMessage('Login přidán - '.$row->code, 'success');
				$this->redirect('Admin:Default');
			} catch (Nette\Neon\Exception $ex) {
				$this->flashMessage($ex->getMessage(), 'error');
				$this->redirect('Admin:Default');
			}
		};

		/**
		 * Columns
		 */
		$datagrid->addColumnText('id', 'Id')
			->addAttributes(["width" => "6%"])
			->setSortable()
			->setAlign('left');
		$datagrid->addColumnText('code', 'Přihlašovací kód');
		$datagrid->addColumnText('note', 'Poznámka');


		/**
		 * Filters
		 */
		$datagrid->addFilterText('code', 'Search', ['code']);
		$datagrid->addFilterText('note', 'Search', ['note']);


		/**
		 * Actions
		 */
		$datagrid->addAction('delete', '', 'deleteLogin!')
			->setIcon('trash')
			->setTitle('Delete')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm('Odstranit %s?', 'id');


		return $datagrid;
	}

	public function handleDelete($id){
		$this->boxesManager->delete($id);
		$this->flashMessage('Odstraněno', 'success');
		$this->redirect('Admin:Default');
	}

	public function handleDeleteUnlock($id){
		$this->unlocksManager->delete($id);
		$this->flashMessage('Odstraněno', 'success');
		$this->redirect('Admin:Default');
	}

	public function handleDeleteLogin($id){
		$this->loginKeysManager->delete($id);
		$this->flashMessage('Odstraněno', 'success');
		$this->redirect('Admin:Default');
	}
}
