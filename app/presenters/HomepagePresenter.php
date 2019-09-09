<?php

namespace App\Presenters;

use App\Model\LoginkeysManager;
use Nette;
use Tracy\Debugger;


class HomepagePresenter extends Nette\Application\UI\Presenter
{
	/**
	 * @var LoginkeysManager
	 */
	private $loginKeysManager;

	/**
	 * HomepagePresenter constructor.
	 * @param LoginkeysManager $loginKeysManager
	 */
	public function __construct(LoginkeysManager $loginKeysManager)
	{
		$this->loginKeysManager = $loginKeysManager;
	}

	protected function beforeRender(){
		if($this->getUser()->isLoggedIn()) {
			$this->redirect('Boxes:Default');
		}
	}

	protected function createComponentSimpleLoginForm()
	{
		$form = new Nette\Application\UI\Form();
		$form->addText('code', 'Tajný přihlašovací kód:')->setAttribute("placeholder","login-XXXX-XXXX")->setRequired("Vyplntě kód")
		->setAttribute("autocomplete","off");
		$form->addSubmit('submit', 'Pokračovat');
		$form->onSuccess[] = function($form) {
			$values = $form->getValues();
			try{
				$this->getUser()->login('boxes-user',$values['code']);
				$this->redirect('Boxes:Default');
			} catch (Nette\Security\AuthenticationException $ex) {
				$this->flashMessage('Špatně zadaný tajný přihlašovací kód. Zkuste to znovu', 'error');
				$this->redirect('Homepage:Default');
			}
		};
		return $form;
	}
}
