<?php

namespace Devnix\Prestablocks;

/**
 * Extra funcionality for Prestashop admin controller. If you load a controller with
 * an action parameter (eg. https://example.com/admin123/index.php?controller=AdminExample&token=92c0945100db2e&action=edit
 * would call a public function editAction())
 * 
 * @author Pablo Largo Mohedano <devnix.code@gmail.com>
 */

class ModuleAdminController extends \ModuleAdminController {
	 public function __construct() {
	 	if (!empty(Tools::getValue('action'))) {
	 		$method = Tools::getValue('action').'Action';
	 		if (method_exists($this, $method)) {
	 			$this->$method();
	 		}

	 	}

	 	parent::__construct();
	 }
}