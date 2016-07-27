<?php

/**
 * Use this class to create the module class instead of Prestashop's one to use a better API.
 *
 * @author Pablo Largo Mohedano <devnix.code@gmail.com>
 */

namespace Devnix\Prestablocks;

use \Tab;
use \Language;

if (!defined('_PS_VERSION_'))
    exit;

class Module extends \Module {
    protected $removeDatabaseOnUninstall = false;

    public function install() {
        $return = true;

        foreach ($this->getModels() as $model) {
            $return = $return && $model->createDatabase();
            $return = $return && $model->createMissingColumns();
        }

        return parent::install() && $return;
    }

    public function uninstall() {
        $return = true;

        if ($this->removeDatabaseOnUninstall) {
            foreach ($this->getModels() as $model) {
              $return = $return && $model->removeDatabase();
            }
        }

        return  parent::uninstall() && $return;
    }

    protected function getModels() {
        foreach (glob(_PS_MODULE_DIR_.$this->name.'/models/*.php') as $path) {
            require_once $path;
            $className = /*'Models\\'.*/pathinfo($path, PATHINFO_FILENAME);

            $models[] = new $className;
        }
        return $models;
    }

    /**
     * @since 1.0
     * @param array $tabs Array of tabs information
     * <code>
     * [
     *     [
     *         'name'      => 'CRUD Completo',
     *         'className' => 'AdminCrudModels',
     *         'active'    => 1,
     *         //submenus
     *         'childs'    => [
     *             [
     *                 'active'    => 1,
     *                 'name'      => 'CRUD Model',
     *                 'className' => 'AdminCrudModels',
     *             ],
     *             [
     *                 'active'    => 1,
     *                 'name'      => 'Custom Operation',
     *                 'className' => 'AdminCrudCustomOperationController',
     *             ],
     *         ],
     *     ],
     * ]
     * </code>
     * @param int $id_parent Id of the parent tab. Defaults to 0 (first tab). You can hide the tab using -1
     * @return bool
     */

    public function addTab($tabs, $id_parent = 0) {
        foreach ($tabs as $tab) {
            $tabModel             = new Tab();
            $tabModel->module     = $this->name;
            $tabModel->active     = $tab['active'];
            $tabModel->class_name = $tab['className'];
            $tabModel->id_parent  = $id_parent;

            //tab text in each language
            foreach (Language::getLanguages(true) as $lang) {
                $tabModel->name[$lang['id_lang']] = $tab['name'];
            }

            $tabModel->add();

            //submenus of the tab
            if (isset($tab['childs']) && is_array($tab['childs'])) {
                $this->addTab($tab['childs'], Tab::getIdFromClassName($tab['className']));
            }
        }
        return true;
    }

    /**
     * @since 1.0
     * Remove a tab and its childrens from the backoffice menu
     * @param array $tabs Array of tabs information
     * @return bool
     */

    protected function removeTab($tabs) {
        foreach ($tabs as $tab) {
            $id_tab = (int) Tab::getIdFromClassName($tab["className"]);
            if ($id_tab) {
                $tabModel = new Tab($id_tab);
                $tabModel->delete();
            }

            if (isset($tab["childs"]) && is_array($tab["childs"])) {
                $this->removeTab($tab["childs"]);
            }
        }

        return true;
    }


}
