# Prestablocks

[![Gitter](https://badges.gitter.im/DevNIX/prestablocks.svg)](https://gitter.im/DevNIX/prestablocks?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

Opinionated framework to help building Prestashop modules faster on early development state in experimental phase.

## Example module constructor (may change during development)

PS: You can not type `use Devnix\Prestablocks\Module;` to abbreviate the class declaration to `class MyCustomModule extends Module` because Prestashop scans the file with `eval()`, leaving to crashes.

### mycustommodule.php
```php
<?php

if (!defined('_PS_VERSION_'))
	exit;

require_once __DIR__.'/vendor/autoload.php';

class MyCustomModule extends Devnix\Prestablocks\Module {
  private $tabs;

  public function __construct() {
    $this->name = 'mycustommodule';
    $this->tab = 'others';
    $this->version = '0.1';
    $this->author = 'Dev_NIX';
    $this->need_instance = 0;
    $this->is_configurable = true;
    $this->ps_versions_compilancy = array('min' => '1.5', 'max' => '1.6');
    $this->bootstrap = true;

    // Prestablocks can create and destroy a database dinamically on installation based on Prestashop ActiveRecord models.
    // This flag is set to false by default to avoid accidental loss of data. Set it to true to clean up your tables and 
    // schema on "reset" or "uninstall"
    $this->removeDatabaseOnUninstall = false;   

    parent::__construct();

    $this->displayName = $this->l('This is a test module');
    $this->description = $this->l('');
    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    $this->tabs = [
      [
      'name'      => 'This is a tab',
      'className' => 'AdminMyCustomTab',
      'active'    => 1,
      ]
    ];

  }

  public function install() {
    return
      parent::install() &&
      $this->addTab($this->tabs, -1) &&   // Implemented function by Prestablocks. Seemed like a standard in a lot of examples, and it's a repeated code never implemented.
      $this->registerHook('actionDispatcher');
  }

  public function uninstall() {
    return parent::uninstall() && $this->removeTab($this->tabs);
  }

  public function hookActionDispatcher() {
    require_once __DIR__.'/vendor/autoload.php';            // Must figure a better way to autoload your module dependencies on your custom classes
  }

}

```

## Database creation

Prestablocks can help you automatically creating the tables and adding columns if they already exists when installing your module.

To use this feature, you just have to create a folder named "models" in the root of your plugin, and a file per class. Prestablocks will load a standard Prestashop model, with an extra field to specify more accurately the column data type. For example:

### models/Person.php
```php
<?php

use Devnix\Prestablocks\ObjectModel;

if (!defined('_PS_VERSION_'))
  exit;

class Person extends ObjectModel {    // Loading the Prestablocks' ObjectModel (extending the Prestashop's one). To use the Prestashop's original class, extend \ObjectModel
  public $id_person;
  public $active;
  public $age;
  public $name;
  public $gender;

  public static $definition = [
    'table'	=> 'person',
    'primary' => 'id_person',
    //'multilang' => true,
    'fields' => [
      'id_person' => [
        'type' => self::TYPE_INT,
        'validate' => 'isInt'
      ],
      'active' => [
        'type' => self::TYPE_BOOL,
        '@type' => 'int',               // Introduced by Prestablocks. Required to specify the column type for MySQL
        'required' => true,
      ],
      'age' => [
        'type' => self::TYPE_INT,
        '@type' => 'int',
        'validate' => 'isInt',
        'required' => true
      ],
      'name' => [
        'type' => self::TYPE_STRING,
        '@type' => 'varchar(255)',
        'size'  => 255,
        'required' => true
      ],
      'gender' => [
        'type' => self::TYPE_STRING,
        '@type' => 'varchar(255)',
        'size'  => 255,
      ]
    ]
  ];

  public static function loadOneByName($name) {
    // Do a query to the database to get the id of the wanted Person
    $result = Db::getInstance()->getRow('
      SELECT id_person
      FROM `'._DB_PREFIX_.'person` person
      WHERE person.`name` LIKE '.$name
    );
    
    // Check if we have no results and return false in that case
    if (sizeof($query) === 0) {
      return false;
    }
		
    // Get the if of the first result
    $id = $query[0]['id_person'];
		
    // Return a new model instance populated with the found user
    return new Person($id);
  }
}
```

Now, each time you add a new field to your model, you just have to press `Reset`, and enjoy how magic works!
