<?php
/**
* 2007-2023 Prestashop.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade prestashop to newer
* versions in the future. If you wish to customize prestashop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Prestashop SA
*  @copyright 2007-2023 Prestashop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of prestashop SA
*  https://www.phptools.online/php-checker/
*/
if (!defined('_PS_VERSION_')) {
    exit;
}

class Rsi_generator extends Module
{
    private $_html = '';
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'rsi_generator';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'rsi';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Module generator');
        $this->description = $this->l('Create modules easy');
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        Configuration::updateValue('RSI_GENERATOR_MODULENAME', 'modulename');
        Configuration::updateValue('RSI_GENERATOR_FIELDS', '');
        Configuration::updateValue('RSI_GENERATOR_CATEGORY', '');
        Configuration::updateValue('RSI_GENERATOR_HOOKS', '');
        Configuration::updateValue('RSI_GENERATOR_BOJS', false);
        Configuration::updateValue('RSI_GENERATOR_BOCSS', false);
        Configuration::updateValue('RSI_GENERATOR_FOJS', false);
        Configuration::updateValue('RSI_GENERATOR_FOCSS', false);
        Configuration::updateValue('RSI_GENERATOR_CONTROLLER', false);
        Configuration::updateValue('RSI_GENERATOR_COPY', '
* 2007-2023 Company
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
*
* DISCLAIMER
*
*  @author    Company SA 
*  @copyright 2007-2023 Company SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of Company SA
');

        return parent::install();
    }

    public function uninstall()
    {
        $deleteall = Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute('SELECT * FROM `'._DB_PREFIX_."configuration` WHERE name LIKE '%RSI_GENERATOR%'");
        foreach ($deleteall as $delete) {
            Configuration::deleteByName($delete);
        }

        return parent::uninstall();
    }

    public function deleteDirectory($dirPath)
    {
        if (!is_dir($dirPath)) {
            $this->displayError('is not a directory');
        }
        if ('/' != substr($dirPath, strlen($dirPath) - 1, 1)) {
            $dirPath .= '/';
        }
        $files = glob($dirPath.'*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    public function getContent()
    {
        if (@Tools::getIsset($_GET['section'])) {
            $index_section = Tools::getValue('section');
        } else {
            $index_section = 1;
        }
        if (Tools::isSubmit('submitRsi_generatorModule')) {
            $postp = $this->postProcess();
        }
        if (Tools::isSubmit('deletem')) {
            $this->deleteDirectory('../modules/rsi_generator/generated/'.Tools::getValue('restorefieldspre').Tools::getValue('restorefields').'/');
            Configuration::deleteByName('RSI_GENERATOR_SAVE_'.strtoupper(Tools::getValue('restorefields')));
            unlink('../modules/rsi_generator/generated/'.Tools::getValue('restorefieldspre').Tools::getValue('restorefields').'.zip');
            $msg = $this->displayConfirmation('Deleted');
        }
        if (Tools::isSubmit('restore')) {
            $values = json_decode(Configuration::get('RSI_GENERATOR_SAVE_'.strtoupper(Tools::getValue('restorefields'))));
            foreach ($values as $k => $v) {
                Configuration::updateValue(str_replace('[]', '', $k), $v);
            }
            $msg = $this->displayConfirmation('Loaded');
        }
        if (Tools::isSubmit('downloadm')) {
            $file_url = '../modules/rsi_generator/generated/'.Tools::getValue('restorefieldspre').Tools::getValue('restorefields').'.zip';
            header('Content-Type: application/zip');
            header('Content-Transfer-Encoding: Binary');
            header('Content-Length: '.filesize($file_url));
            header('Content-disposition: attachment; filename="'.basename($file_url).'"');
            readfile($file_url);
        }
        $this->context->smarty->assign([
            'renderForm' => $this->renderForm(),
            'jira' => Configuration::get('RSI_GENERATOR_JIRA'),
            'git' => Configuration::get('RSI_GENERATOR_GIT'),
            'employee' => strpos($this->context->employee->email, 'yourcompany'),
            'section_adminpage' => $index_section,
            'saved' => $this->getFields(),
            'id_shop' => $this->context->shop->id,
            'form' => (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http')."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
            'module_dir' => _PS_BASE_URL_._MODULE_DIR_.'rsi_generator/', ]);
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return (isset($msg) ? $msg : '').(isset($postp) ? $postp : '').$output;
    }

    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRsi_generatorModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        $hooksnames = Hook::getHooks();
        foreach ($hooksnames as $key => $type) {
            $hooksnames[$key]['name'] = $type['name'].'('.$type['title'].')';
            $hooksnames[$key]['id_option'] = $type['name'];
        }
        $classList = [
            [
                'id_option' => 'Feature',
                'name' => 'Features',
            ],
            [
                'id_option' => 'Country',
                'name' => 'Country',
            ],
            [
                'id_option' => 'Shop',
                'name' => 'Shop',
            ],
            [
                'id_option' => 'Attribute',
                'name' => 'Attributes',
            ],
            [
                'id_option' => 'Manufacturer',
                'name' => 'Manufacturer',
            ],
            [
                'id_option' => 'Group',
                'name' => 'Group',
            ],
            [
                'id_option' => 'Supplier',
                'name' => 'Supplier',
            ],
            [
                'id_option' => 'Currency',
                'name' => 'Currency',
            ],
            [
                'id_option' => 'Language',
                'name' => 'Language',
            ],
            [
                'id_option' => 'PaymentModule',
                'name' => 'Payment',
            ],
            [
                'id_option' => 'Carrier',
                'name' => 'Carrier',
            ],
            [
                'id_option' => 'OrderState',
                'name' => 'Order status',
            ],
            [
                'id_option' => 'CMS',
                'name' => 'CMS pages',
            ],
            [
                'id_option' => 'Employee',
                'name' => 'Employees',
            ],
        ];

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'required' => true,
                        'hint' => $this->l('Try to use descriptive module name'),
                        'label' => $this->l('Module name'),
                        'validation' => 'isModuleName',
                        'desc' => $this->l('Enter your module name without the prefix'),
                        'name' => 'RSI_GENERATOR_MODULENAME',
                    ],
                    [
                        'type' => 'text',
                        'required' => true,
                        'hint' => $this->l('The company or developer'),
                        'label' => $this->l('Author'),
                        'desc' => $this->l('Author of the module'),
                        'name' => 'RSI_GENERATOR_AUTHOR',
                    ],
                    [
                        'type' => 'text',
                        'required' => true,
                        'hint' => $this->l('The module version'),
                        'label' => $this->l('Module version'),
                        'desc' => $this->l('Module version with 3 digits'),
                        'name' => 'RSI_GENERATOR_VERSION',
                    ],
                    [
                        'type' => 'text',
                        'required' => true,
                        'label' => $this->l('Title'),
                        'desc' => $this->l('A title for the module'),
                        'name' => 'RSI_GENERATOR_TITLE',
                    ],
                    [
                        'type' => 'text',
                        'required' => true,
                        'label' => $this->l('Description'),
                        'desc' => $this->l('A description for the module'),
                        'name' => 'RSI_GENERATOR_DESC',
                    ],
                    [
                        'type' => 'text',
                        'required' => true,
                        'label' => $this->l('PREFIX'),
                        'desc' => $this->l('A prefix to the module name (genrerally, your short company name, in lowercase and with _ , like: rsi_)'),
                        'name' => 'RSI_GENERATOR_PREFIX',
                    ],
                    [
                        'type' => 'file',
                        'required' => true,
                        'label' => $this->l('Icon'),
                        'desc' => $this->l('A icon for the module (.png, 64px to 128px)'),
                        'name' => 'RSI_GENERATOR_ICON',
                    ],
                    [
                        'type' => 'select',
                        'multiple' => true,
                        'class' => 'chosen',
                        'name' => 'RSI_GENERATOR_HOOKS[]',
                        'desc' => $this->l('Select the hooks where you want the module'),
                        'label' => $this->l('Hooks'),
                        'options' => [
                            'query' => Hook::getHooks(),
                            'id' => 'name',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'multiple' => true,
                        'class' => 'chosen',
                        'name' => 'RSI_GENERATOR_CONTROLLER[]',
                        'desc' => $this->l('If you need a select with predefined class options, select here (lsit of manufacturers, suppliers, etc)'),
                        'label' => $this->l('Class'),
                        'options' => [
                            'query' => $classList,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Text Fields'),
                        'desc' => $this->l('Text fields only. Put the fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_FIELDS',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Multilingual text fields'),
                        'desc' => $this->l('Text fields only with language selection. Put the fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_FIELDSLANG',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Text Fields with button'),
                        'desc' => $this->l('A text field with action button.Put the fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_FIELDSBUTTON',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Upload file field'),
                        'desc' => $this->l('A upload filefield.Put the fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_FILE',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Select Fields'),
                        'desc' => $this->l('Default select field for multiple options.Put the select fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_SELECTFIELDS',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Boolean Fields'),
                        'desc' => $this->l('True or false fields.Put the boolean fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_BOOLFIELDS',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Checkbox Fields'),
                        'desc' => $this->l('Put the checkbox fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_CHECKFIELDS',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Color Fields'),
                        'desc' => $this->l('Color select fields.Put the color fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_COLOR',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Date Fields'),
                        'desc' => $this->l('Date time fields.Put the date fields names that you need to configure your module, separated by comma'),
                        'name' => 'RSI_GENERATOR_DATE',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Category'),
                        'name' => 'RSI_GENERATOR_CATEGORY',
                        'desc' => $this->l('A category select.'),
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enable'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disable'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Back office JS'),
                        'name' => 'RSI_GENERATOR_BOJS',
                        'desc' => $this->l('Need a back office Javascript?'),
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enable'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disable'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Front office JS'),
                        'name' => 'RSI_GENERATOR_FOJS',
                        'desc' => $this->l('Need a front office Javascript?'),
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enable'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disable'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Front office CSS'),
                        'name' => 'RSI_GENERATOR_FOCSS',
                        'desc' => $this->l('Need a front office CSS?'),
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enable'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disable'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Back office CSS'),
                        'name' => 'RSI_GENERATOR_BOCSS',
                        'desc' => $this->l('Need a back office CSS?'),
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enable'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disable'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'textarea',
                        'rows' => 20,
                        'cols' => 20,
                        'label' => $this->l('Liscence'),
                        'desc' => $this->l('Set the Liscence for all files (every line must start with * )'),
                        'name' => 'RSI_GENERATOR_COPY',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'RSI_GENERATOR_COPYRIGHT',
                        'label' => 'Copyright',
                        'desc' => $this->l('Add a contact info and  a copyright for the help section (leave blank to disable)'),
                        'rows' => 5,
                        'cols' => 20,
                        'lang' => false,
                        'required' => false,
                        'autoload_rte' => true,
                        'hint' => 'Invalid characters: <>;=#{}',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'RSI_GENERATOR_HOWTO',
                        'label' => 'Dashboard info',
                        'desc' => $this->l('Add a dashboard information of how to use the module, etc. (leave blank to disable)'),
                        'rows' => 5,
                        'cols' => 20,
                        'lang' => false,
                        'required' => false,
                        'autoload_rte' => true,
                        'hint' => 'Invalid characters: <>;=#{}',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            'RSI_GENERATOR_COPY' => Tools::getValue('RSI_GENERATOR_COPY', Configuration::get('RSI_GENERATOR_COPY')),
            'RSI_GENERATOR_PREFIX' => Tools::getValue('RSI_GENERATOR_PREFIX', Configuration::get('RSI_GENERATOR_PREFIX')),
            'RSI_GENERATOR_ICON' => Tools::getValue('RSI_GENERATOR_ICON', Configuration::get('RSI_GENERATOR_ICON')),
            'RSI_GENERATOR_HOWTO' => Tools::getValue('RSI_GENERATOR_HOWTO', Configuration::get('RSI_GENERATOR_HOWTO')),
            'RSI_GENERATOR_COPYRIGHT' => Tools::getValue('RSI_GENERATOR_COPYRIGHT', Configuration::get('RSI_GENERATOR_COPYRIGHT')),
            'RSI_GENERATOR_MODULENAME' => Tools::getValue('RSI_GENERATOR_MODULENAME', Configuration::get('RSI_GENERATOR_MODULENAME')),
            'RSI_GENERATOR_AUTHOR' => Tools::getValue('RSI_GENERATOR_AUTHOR', Configuration::get('RSI_GENERATOR_AUTHOR')),
            'RSI_GENERATOR_VERSION' => Tools::getValue('RSI_GENERATOR_VERSION', Configuration::get('RSI_GENERATOR_VERSION')),
            'RSI_GENERATOR_TITLE' => Tools::getValue('RSI_GENERATOR_TITLE', Configuration::get('RSI_GENERATOR_TITLE')),
            'RSI_GENERATOR_DESC' => Tools::getValue('RSI_GENERATOR_DESC', Configuration::get('RSI_GENERATOR_DESC')),
            'RSI_GENERATOR_CATEGORY' => Tools::getValue('RSI_GENERATOR_CATEGORY', Configuration::get('RSI_GENERATOR_CATEGORY')),
            'RSI_GENERATOR_FIELDS' => Tools::getValue('RSI_GENERATOR_FIELDS', Configuration::get('RSI_GENERATOR_FIELDS')),
            'RSI_GENERATOR_FIELDSLANG' => Tools::getValue('RSI_GENERATOR_FIELDSLANG', Configuration::get('RSI_GENERATOR_FIELDSLANG')),
            'RSI_GENERATOR_FIELDSBUTTON' => Tools::getValue('RSI_GENERATOR_FIELDSBUTTON', Configuration::get('RSI_GENERATOR_FIELDSBUTTON')),
            'RSI_GENERATOR_FILE' => Tools::getValue('RSI_GENERATOR_FILE', Configuration::get('RSI_GENERATOR_FILE')),
            'RSI_GENERATOR_COLOR' => Tools::getValue('RSI_GENERATOR_COLOR', Configuration::get('RSI_GENERATOR_COLOR')),
            'RSI_GENERATOR_DATE' => Tools::getValue('RSI_GENERATOR_DATE', Configuration::get('RSI_GENERATOR_DATE')),
            'RSI_GENERATOR_CHECKFIELDS' => Tools::getValue('RSI_GENERATOR_CHECKFIELDS', Configuration::get('RSI_GENERATOR_CHECKFIELDS')),
            'RSI_GENERATOR_BOOLFIELDS' => Tools::getValue('RSI_GENERATOR_BOOLFIELDS', Configuration::get('RSI_GENERATOR_BOOLFIELDS')),
            'RSI_GENERATOR_SELECTFIELDS' => Tools::getValue('RSI_GENERATOR_SELECTFIELDS', Configuration::get('RSI_GENERATOR_SELECTFIELDS')),
            'RSI_GENERATOR_HOOKS[]' => Tools::getValue('RSI_GENERATOR_HOOKS', Configuration::get('RSI_GENERATOR_HOOKS')),
            'RSI_GENERATOR_BOJS' => Tools::getValue('RSI_GENERATOR_BOJS', Configuration::get('RSI_GENERATOR_BOJS')),
            'RSI_GENERATOR_BOCSS' => Tools::getValue('RSI_GENERATOR_BOCSS', Configuration::get('RSI_GENERATOR_BOCSS')),
            'RSI_GENERATOR_FOJS' => Tools::getValue('RSI_GENERATOR_FOJS', Configuration::get('RSI_GENERATOR_FOJS')),
            'RSI_GENERATOR_FOCSS' => Tools::getValue('RSI_GENERATOR_FOCSS', Configuration::get('RSI_GENERATOR_FOCSS')),
            'RSI_GENERATOR_CONTROLLER[]' => Tools::getValue('RSI_GENERATOR_CONTROLLER', Configuration::get('RSI_GENERATOR_CONTROLLER')),
        ];
    }

    public function renderFields($field, $lang)
    {
        $field = PHP_EOL.'        if (!Configuration::updateValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($field)).'\', false)) {
            return false;
        }';

        return $field;
    }

    public function renderFieldsValues($field)
    {
        $getFieldsValues = PHP_EOL.'            \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(str_replace(' ', '', $field)).'\' =>  Tools::getValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(str_replace(' ', '', $field)).'\', Configuration::get(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(str_replace(' ', '', $field)).'\')),';
        
        return $getFieldsValues;
    }
    public function renderFieldsValuesLang($field)
    {
        $getFieldsValuesLang = PHP_EOL.'            $fields_values[\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(str_replace(' ', '', $field)).'\'][$lang[\'id_lang\']] =  Tools::getValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(str_replace(' ', '', $field)).'_\', Configuration::get(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(str_replace(' ', '', $field)).'\', $lang[\'id_lang\']));';
    
        return $getFieldsValuesLang;
    }
    public function renderHookValues($field, $lang)
    {
        $hookValues = '        $'.str_replace(' ', '', $field).' = Configuration::get(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(str_replace(' ', '', $field)).'\''.($lang == true ? ', $this->context->language->id' : '').');'.PHP_EOL;

        return $hookValues;
    }

    public function renderSmartyValues($field)
    {
        $smartyVariables = PHP_EOL.'            \''.str_replace(' ', '', $field).'\' => $'.str_replace(' ', '', $field).',';

        return $smartyVariables;
    }

    public function getFields()
    {
        $result = [];
        if (!$links = Db::getInstance()
                        ->ExecuteS(
                            'SELECT * FROM '._DB_PREFIX_.'configuration WHERE name LIKE "%RSI_GENERATOR_SAVE_%"'
                        )
        ) {
            return false;
        }
        $i = 0;

        foreach ($links as $link) {
            foreach (json_decode($link['value'], true) as $key => $value) {
                $result[$i][$key] = $value;
            }
            ++$i;
        }

        return $result;
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        $save = json_encode($form_values);
        foreach (array_keys($form_values) as $key) {
            if ('RSI_GENERATOR_CONTROLLER[]' == $key) {
                if ($controller = Tools::getValue('RSI_GENERATOR_CONTROLLER')) {
                    Configuration::updateValue(
                        'RSI_GENERATOR_CONTROLLER',
                        implode(
                            ',',
                            $controller
                        )
                    );
                } elseif (Shop::CONTEXT_SHOP == Shop::getContext() || Shop::CONTEXT_GROUP == Shop::getContext()) {
                    Configuration::deleteFromContext('RSI_GENERATOR_CONTROLLER');
                }
            } elseif ('RSI_GENERATOR_HOOKS[]' == $key) {
                if ($hooks = Tools::getValue('RSI_GENERATOR_HOOKS')) {
                    Configuration::updateValue(
                        'RSI_GENERATOR_HOOKS',
                        implode(
                            ',',
                            $hooks
                        )
                    );
                } elseif (Shop::CONTEXT_SHOP == Shop::getContext() || Shop::CONTEXT_GROUP == Shop::getContext()) {
                    Configuration::deleteFromContext('RSI_GENERATOR_HOOKS');
                }
            } elseif ('RSI_GENERATOR_COPYRIGHT' == $key || 'RSI_GENERATOR_HOWTO' == $key) {
                Configuration::updateValue($key, Tools::getValue($key), true);
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
        /* guardar configuracion json */

        /* Creacion de directorios */
        if (!Validate::isModuleName(Configuration::get('RSI_GENERATOR_MODULENAME')) && null == Tools::getValue('restorefields')) {
            return $this->displayError($this->l('Invalid module name').'<br/>');
        } else {
            if (null != Configuration::get('RSI_GENERATOR_MODULENAME') || '' != Configuration::get('RSI_GENERATOR_MODULENAME')) {
                Configuration::updateValue('RSI_GENERATOR_SAVE_'.strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')), $save);
            }
        }
        if (!file_exists('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')))) {
            mkdir('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')), 0777, true);
        }
        /* logo */
        copy('../modules/rsi_generator/skeleton/logo.png', '../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/logo.png');
        copy('../modules/rsi_generator/index.php', '../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/index.php');
        $source = '../modules/rsi_generator/skeleton/views';
        $dest = '../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views';

        @mkdir($dest, 0755);
        foreach (
            $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST) as $item
            ) {
            if ($item->isDir()) {
                @mkdir($dest.DIRECTORY_SEPARATOR.$iterator->getSubPathname());
            } else {
                copy($item, $dest.DIRECTORY_SEPARATOR.$iterator->getSubPathname());
            }
        }
        if (1 == Configuration::get('RSI_GENERATOR_FOCSS') or 1 == Configuration::get('RSI_GENERATOR_FOJS')) {
        }
        if (1 == Configuration::get('RSI_GENERATOR_BOCSS') or 1 == Configuration::get('RSI_GENERATOR_BOJS')) {
        }
        /* Generate module file */
        $fp = fopen('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'.php', 'w');

        /* copy icon */
        $errors = null;
        $newfilekey = 'RSI_GENERATOR_ICON';
        if (isset($_FILES[$newfilekey]) && isset($_FILES[$newfilekey]['tmp_name']) && !empty($_FILES[$newfilekey]['tmp_name'])) {
            if (!move_uploaded_file(
                    $_FILES[$newfilekey]['tmp_name'],
                    '../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/logo.png'
                )
                ) {
                $errors .= $this->l('File upload error.');
            }
        }

        /* define class and public fuinction */
        $phpcontent = '<?php'.PHP_EOL.'/**'.PHP_EOL.''.Configuration::get('RSI_GENERATOR_COPY').''.PHP_EOL.'*/'.PHP_EOL.'if (!defined(\'_PS_VERSION_\')) {'.PHP_EOL.'    exit;'.PHP_EOL.'}'.PHP_EOL.'class '.ucfirst(Configuration::get('RSI_GENERATOR_PREFIX')).ucwords(Configuration::get('RSI_GENERATOR_MODULENAME')).' extends Module'.PHP_EOL.'    {'.PHP_EOL.'    private $_html = \'\';'.PHP_EOL.'    protected $config_form = false;
    public function __construct()
    {
        $this->name = \''.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'\';
        $this->tab = \'administration\';
        $this->version = \''.Configuration::get('RSI_GENERATOR_VERSION').'\';
        $this->author = \''.Configuration::get('RSI_GENERATOR_AUTHOR').'\';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l(\''.Configuration::get('RSI_GENERATOR_TITLE').'\');
        $this->description = $this->l(\''.Configuration::get('RSI_GENERATOR_DESC').'\');
        $this->ps_versions_compliancy = array(\'min\' => \'1.6\', \'max\' => _PS_VERSION_);
    }';
        $fields = null;
        $getFieldsValues = null;
        $fieldsValues = null;
        $smartyFieldsValues = null;
        $getFieldsValuesLang = null;
        if (Configuration::get('RSI_GENERATOR_HOOKS') && '' != Configuration::get('RSI_GENERATOR_HOOKS')) {
            $fields .= '
        if (parent::install() == false ||'.PHP_EOL;
            foreach (explode(',', Configuration::get('RSI_GENERATOR_HOOKS')) as $field) {
                $fields .= '        $this->registerHook(\'hook'.str_replace('display', '', $field).'\') == false ||'.PHP_EOL;
            }
            $fields = substr($fields, 0, -4);
            $fields .= ')
        {
            return false; 
        }';
        }
        if (Configuration::get('RSI_GENERATOR_FIELDSBUTTON')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_FIELDSBUTTON')) as $field) {
                $fields .= $this->renderFields($field, false);
                $fieldsValues .= $this->renderHookValues($field, false);
                $getFieldsValues .= $this->renderFieldsValues($field);
                $smartyFieldsValues .= $this->renderSmartyValues($field);
            }
        }
        if (Configuration::get('RSI_GENERATOR_FILE')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_FILE')) as $field) {
                $fields .= $this->renderFields('FILE_'.$field, false);
                $fieldsValues .= $this->renderHookValues('FILE_'.$field, false);
                $smartyFieldsValues .= $this->renderSmartyValues('FILE_'.$field, false);
                $getFieldsValues .= $this->renderFieldsValues('FILE_'.$field, false);
            }
            @mkdir('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/upload', 0755);
            copy('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/index.php', '../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/upload/index.php');
        }
        if (Configuration::get('RSI_GENERATOR_FIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_FIELDS')) as $field) {
                $fields .= $this->renderFields($field, false);
                $fieldsValues .= $this->renderHookValues($field, false);
                $smartyFieldsValues .= $this->renderSmartyValues($field);
                $getFieldsValues .= $this->renderFieldsValues($field);
            }
        }
        if (Configuration::get('RSI_GENERATOR_FIELDSLANG')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_FIELDSLANG')) as $field) {
                $fields .= $this->renderFields($field, false);
                $fieldsValues .= $this->renderHookValues($field, true);
                $smartyFieldsValues .= $this->renderSmartyValues($field, true);
                $getFieldsValuesLang .= $this->renderFieldsValuesLang($field);
            }
        }
        if (Configuration::get('RSI_GENERATOR_COLOR')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_COLOR')) as $field) {
                $fields .= $this->renderFields($field, false);
                $fieldsValues .= $this->renderHookValues($field, false);
                $smartyFieldsValues .= $this->renderSmartyValues($field);
                $getFieldsValues .= $this->renderFieldsValues($field);
            }
        }
        if (Configuration::get('RSI_GENERATOR_DATE')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_DATE')) as $field) {
                $fields .= $this->renderFields($field, false);
                $fieldsValues .= $this->renderHookValues($field, false);
                $smartyFieldsValues .= $this->renderSmartyValues($field);
                $getFieldsValues .= $this->renderFieldsValues($field);
            }
        }
        if (Configuration::get('RSI_GENERATOR_SELECTFIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_SELECTFIELDS')) as $field) {
                $fields .= $this->renderFields($field, false);
                $fieldsValues .= $this->renderHookValues($field, false);
                $smartyFieldsValues .= $this->renderSmartyValues($field);
                $getFieldsValues .= $this->renderFieldsValues($field);
            }
        }
        if (Configuration::get('RSI_GENERATOR_BOOLFIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_BOOLFIELDS')) as $field) {
                $fields .= $this->renderFields($field, false);
                $fieldsValues .= $this->renderHookValues($field, false);
                $smartyFieldsValues .= $this->renderSmartyValues($field);
                $getFieldsValues .= $this->renderFieldsValues($field);
            }
        }
        if (Configuration::get('RSI_GENERATOR_CHECKFIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_CHECKFIELDS')) as $field) {
                $fields .= $this->renderFields($field.'_1', false);
                $fieldsValues .= $this->renderHookValues($field.'_1', false);
                $smartyFieldsValues .= $this->renderSmartyValues($field.'_1', false);
                $getFieldsValues .= $this->renderFieldsValues($field.'_1');
                $fields .= $this->renderFields($field.'_2', false);
                $fieldsValues .= $this->renderHookValues($field.'_2', false);
                $smartyFieldsValues .= $this->renderSmartyValues($field.'_2', false);
                $getFieldsValues .= $this->renderFieldsValues($field.'_2');
            }
        }
        if (1 == Configuration::get('RSI_GENERATOR_CATEGORY')) {
            $fields .= $this->renderFields($field, false);
            $fieldsValues .= $this->renderHookValues($field, false);
            $smartyFieldsValues .= $this->renderSmartyValues($field, false);
            $getFieldsValues .= PHP_EOL.'            \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\' =>  Tools::getValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\', explode(",", Configuration::get(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\'))),'.PHP_EOL;
        }

        /* install function */
        $phpcontent .= ''.PHP_EOL.'    public function install()
    {
        '.$fields.'
        return parent::install();
    }'.PHP_EOL;

        /* uninstall function */
        $phpcontent .= ''.PHP_EOL.'    public function uninstall()
    {
        $deleteall = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS("SELECT name FROM `"._DB_PREFIX_."configuration` WHERE name LIKE \'%'.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'%\'");
        foreach ($deleteall as $delete) {
            Configuration::deleteByName($delete[\'name\']);
        }
        return parent::uninstall();
    }'.PHP_EOL;

        /* hooks */
        $hooks = null;
        if (null != Configuration::get('RSI_GENERATOR_HOOKS')) {
            @mkdir('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views/templates/hook/', 0777, true);
            copy('../modules/rsi_generator/index.php', '../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views/templates/hook/index.php');
        }
        if (null != Configuration::get('RSI_GENERATOR_HOOKS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_HOOKS')) as $hook) {
                $ftpl = fopen('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views/templates/hook/'.$hook.'.tpl', 'wb');
                fwrite($ftpl, '{* '.PHP_EOL.''.Configuration::get('RSI_GENERATOR_COPY').''.PHP_EOL.'*}'.PHP_EOL);
                fclose($ftpl);
                if ('displayHeader' == $hook) {
                    $hooks .= PHP_EOL.'    public function hook'.$hook.'($params)'.PHP_EOL.'    {'.PHP_EOL;
                    if (1 == Configuration::get('RSI_GENERATOR_FOJS')) {
                        $fjs = fopen('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views/js/javascript.js', 'w');
                        fwrite($fjs, '/*!'.PHP_EOL.' '.Configuration::get('RSI_GENERATOR_COPY').''.PHP_EOL.'*/');
                        fclose($fjs);
                        if (_PS_VERSION_ > '1.5.0.0' && _PS_VERSION_ < '1.7.0.0') {
                            $hooks .= '            $this->context->controller->addJS('.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'/views/js/javascript.js\');';
                        }
                        if (_PS_VERSION_ > '1.7.0.0') {
                            $hooks .= '        $this->context->controller->registerJavascript(
                \'modules-'.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'\',
                \'modules/'.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'/views/js/javascript.js\',
                array(\'position\' => \'top\', \'priority\' => 100)
            );'.PHP_EOL;
                        }
                    }
                    if (1 == Configuration::get('RSI_GENERATOR_FOCSS')) {
                        $fcss = fopen('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views/css/style.css', 'w');
                        fwrite($fcss, '/*'.PHP_EOL.''.Configuration::get('RSI_GENERATOR_COPY').''.PHP_EOL.'*/');
                        fclose($fcss);
                        if (_PS_VERSION_ > '1.5.0.0' && _PS_VERSION_ < '1.7.0.0') {
                            $hooks .= '        $this->context->controller->addCSS('.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'/views/css/style.css\', \'all\');';
                        }
                        if (_PS_VERSION_ > '1.7.0.0') {
                            $hooks .= '        $this->context->controller->registerStylesheet(
                \'modules-'.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'\',
                \'modules/'.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'/views/css/style.css\',
                array(\'position\' => \'top\', \'priority\' => 150)
            );'.PHP_EOL;
                        }
                    }
                    $hooks .= '    }'.PHP_EOL;
                } elseif ('backOfficeHeader' == $hook) {
                    $hooks .= PHP_EOL.'    public function hook'.$hook.'($params)'.PHP_EOL.'    {'.PHP_EOL;
                    if (1 == Configuration::get('RSI_GENERATOR_BOJS')) {
                        $fbjs = fopen('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views/js/backjavascript.js', 'w');
                        fwrite($fbjs, '/*!'.PHP_EOL.' '.Configuration::get('RSI_GENERATOR_COPY').''.PHP_EOL.'*/');
                        fclose($fbjs);
                        if (_PS_VERSION_ > '1.5.0.0' && _PS_VERSION_ < '1.7.0.0') {
                            $hooks .= '        $this->context->controller->addJS('.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'/views/js/backjavascript.js\');';
                        }
                        if (_PS_VERSION_ > '1.7.0.0') {
                            $hooks .= '        $this->context->controller->registerJavascript(
                \'modules-'.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'\',
                \'modules/'.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'/views/js/backjavascript.js\',
                array(\'position\' => \'top\', \'priority\' => 100)
            );'.PHP_EOL;
                        }
                    }
                    if (1 == Configuration::get('RSI_GENERATOR_BOCSS')) {
                        $fbcss = fopen('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views/css/backstyle.css', 'w');
                        fwrite($fbcss, '/*'.PHP_EOL.''.Configuration::get('RSI_GENERATOR_COPY').''.PHP_EOL.'*/');
                        fclose($fbcss);
                        if (_PS_VERSION_ > '1.5.0.0' && _PS_VERSION_ < '1.7.0.0') {
                            $hooks .= '        $this->context->controller->addCSS('.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'/views/css/backstyle.css\', \'all\');';
                        }
                        if (_PS_VERSION_ > '1.7.0.0') {
                            $hooks .= '        $this->context->controller->registerStylesheet(
                \'modules-'.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'\',
                \'modules/'.Configuration::get('RSI_GENERATOR_PREFIX').Configuration::get('RSI_GENERATOR_MODULENAME').'/views/css/backstyle.css\',
                array(\'position\' => \'top\', \'priority\' => 150)
                );'.PHP_EOL;
                        }
                    }
                    $hooks .= '    }'.PHP_EOL;
                } else {
                    $hooks .= '    public function hook'.$hook.'($params)'.PHP_EOL.'    {
        '.$fieldsValues.'
            $this->smarty->assign(array(
                '.$smartyFieldsValues.'
            ));
            return $this->display(
                __FILE__,
                \'/views/templates/hook/'.$hook.'.tpl\'
            );
        }'.PHP_EOL;
                }
            }
            $phpcontent .= $hooks;
        }
        /* function get content */
        $phpcontent .= '
    public function getContent()
    {
        if (@Tools::getIsset($_GET[\'section\'])) {
            $index_section = Tools::getValue(\'section\');
        } else {
            $index_section = 1;
        }
        if (((bool)Tools::isSubmit(\'submit'.ucfirst(Configuration::get('RSI_GENERATOR_PREFIX')).Configuration::get('RSI_GENERATOR_MODULENAME').'Module\')) == true) {
            $post = $this->postProcess();
        }
        $this->context->smarty->assign(array(
            \'renderForm\' => $this->renderForm(),
            \'jira\' =>  Configuration::get(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_JIRA\'),
            \'git\' => Configuration::get(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_GIT\'),
            \'employee\' => strpos($this->context->employee->email, \'yourcompany\'),
            \'section_adminpage\' => $index_section,
            \'id_shop\' => $this->context->shop->id,
            \'module_dir\' => _PS_BASE_URL_._MODULE_DIR_.\''.strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/\',
        ));
        $output = $this->context->smarty->fetch($this->local_path.\'views/templates/admin/configure.tpl\');
        return  (isset($post) ? $post : \'\').$output;
    }
        ';
        /* render form */
        $phpcontent .= '
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get(\'PS_BO_ALLOW_EMPLOYEE_FORM_LANG\', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = \'submit'.ucfirst(Configuration::get('RSI_GENERATOR_PREFIX')).Configuration::get('RSI_GENERATOR_MODULENAME').'Module\';
        $helper->currentIndex = $this->context->link->getAdminLink(\'AdminModules\', false)
            .\'&configure=\'.$this->name.\'&tab_module=\'.$this->tab.\'&module_name=\'.$this->name;
        $helper->token = Tools::getAdminTokenLite(\'AdminModules\');
        $helper->tpl_vars = array(
            \'fields_value\' => $this->getConfigFormValues(),
            \'languages\' => $this->context->controller->getLanguages(),
            \'id_language\' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm()));
    }
        
        ';
        /* Render form */
        if (Configuration::get('RSI_GENERATOR_SELECTFIELDS') || Configuration::get('RSI_GENERATOR_CHECKFIELDS')) {
            $checkopt = '$checkopt = array(
                array(
                    \'id_option\' => \'1\',
                    \'name\' => \'Option 1\'
                ),
                array(
                    \'id_option\' => \'2\',
                    \'name\' => \'Option 2\'
                ),
            );';
        }
        if (Configuration::get('RSI_GENERATOR_CATEGORY')) {
            $catopt = '
        $root = Category::getRootCategory();
        $selected_cat = explode(
            \',\',
            Configuration::get(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\')
        );
        $tree = new HelperTreeCategories(\'categories-treeview\');
        $tree->setUseCheckBox(true)
                ->setAttribute(
                    \'is_category_filter\',
                    $root->id
                )
                ->setRootCategory($root->id)
                ->setSelectedCategories($selected_cat)
                ->setUseSearch(true)
                ->setInputName(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\');
        $categoryTreeCol1 = $tree->render();
        ';
        }

        $phpcontent .= '
    protected function getConfigForm()
    {
        '.(isset($checkopt) ? $checkopt : '').'
        '.(isset($catopt) ? $catopt : '').'
        return array(
            \'form\' => array(
                \'legend\' => array(
                    \'title\' => $this->l(\'Settings\'),
                    \'icon\' => \'icon-cogs\',
                ),
                \'input\' => array(';

        $formfields = null;
        if (Configuration::get('RSI_GENERATOR_FIELDSBUTTON')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_FIELDSBUTTON')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'textbutton\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of  '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\',
                \'button\' => [
                    \'label\' => \'do something\',
                    \'attributes\' => [
                        \'onclick\' => "alert(\'something done\');"
                    ]
                ]
            ),';
            }
        }
        if (Configuration::get('RSI_GENERATOR_FILE')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_FILE')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'file\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of  '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'\',
                \'display_image\' => true,
                \'size\' => (file_exists($this->local_path.\'/upload/'.
                strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.png\') ? filesize($this->local_path.\'/upload/'.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.png\') / 200 : (file_exists($this->local_path.\'/upload/'.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.jpg\') ?  filesize($this->local_path.\'/upload/'.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.jpg\') / 200 : null)),
                \'image\' =>(file_exists($this->local_path.\'/upload/'.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.png\') ? ImageManager::thumbnail($this->local_path.\'/upload/'.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.png\',\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.png\', 150, \'png\', true, true) : (file_exists($this->local_path.\'/upload/'.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.jpg\') ? ImageManager::thumbnail($this->local_path.\'/upload/'.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.jpg\', \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FILE_'.strtoupper(trim($formfield)).'.jpg\', 150, \'jpg\', true, true) : false)),
            ),';
            }
        }
        if (Configuration::get('RSI_GENERATOR_FIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_FIELDS')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'text\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of  '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\',
            ),';
            }
        }
        if (Configuration::get('RSI_GENERATOR_FIELDSLANG')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_FIELDS')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'text\',
                \'lang\' => \'true\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of  '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\',
            ),';
            }
        }
        if (Configuration::get('RSI_GENERATOR_COLOR')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_COLOR')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'color\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of  '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\',
            ),';
            }
        }
        if (Configuration::get('RSI_GENERATOR_DATE')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_DATE')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'datetime\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of  '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\',
            ),';
            }
        }
        if (Configuration::get('RSI_GENERATOR_SELECTFIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_SELECTFIELDS')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'select\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\',
                \'options\' => array(
                    \'query\' => $checkopt,
                    \'id\' => \'id_option\',
                    \'name\' => \'name\'
                )
            ),';
            }
        }
        if (Configuration::get('RSI_GENERATOR_BOOLFIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_BOOLFIELDS')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'switch\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of  '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\',
                \'class\' => \'t\',
                \'is_bool\' => true,
                \'values\' => array(
                    array(
                        \'id\' => \'active_on\',
                        \'value\'=> 1,
                        \'label\'=> $this->l(\'Enable\'),
                    ),
                    array(
                        \'id\' => \'active_off\',
                        \'value\'=> 0,
                        \'label\'=> $this->l(\'Disable\'),
                    ),
                ),
            ),';
            }
        }
        if (Configuration::get('RSI_GENERATOR_CHECKFIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_CHECKFIELDS')) as $formfield) {
                $formfields .= '
            array(
                \'type\' => \'checkbox\',
                \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\',
                \'values\' => array(
                    \'query\' => $checkopt,
                    \'id\'    => \'id_option\',
                    \'name\'  => \'name\'
                ),
                \'expand\' => array(                   
                    \'print_total\' => count($checkopt),
                    \'default\' => \'show\',
                    \'show\' => array(\'text\' => $this->l(\'show\'), \'icon\' => \'plus-sign-alt\'),
                    \'hide\' => array(\'text\' => $this->l(\'hide\'), \'icon\' => \'minus-sign-alt\')
                ),
            ),';
            }
        }
        /* generte custom select for classes */
        if (Configuration::get('RSI_GENERATOR_CONTROLLER')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_CONTROLLER')) as $formfield) {
                $getFieldsValues .= '            \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\' =>  Tools::getValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\', Configuration::get(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'\')),'.PHP_EOL;

                if ('Carrier' == $formfield) {
                    $funct = 'getCarriers($this->context->language->id, true)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_carrier\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('Group' == $formfield) {
                    $funct = 'getGroups($this->context->language->id, true)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_group\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('Employee' == $formfield) {
                    $funct = 'getEmployees(false)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_employee\',
                        \'name\'  => \'id_employee\'
                    ),
                ),';
                }
                if ('CMS' == $formfield) {
                    $funct = 'listCms($this->context->language->id, false,true)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_cms\',
                        \'name\'  => \'meta_title\'
                    ),
                ),';
                }
                if ('Country' == $formfield) {
                    $funct = 'getCountries($this->context->language->id, true, false, false)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_country\',
                        \'name\'  => \'country\'
                    ),
                ),';
                }
                if ('Feature' == $formfield) {
                    $funct = 'getFeatures($this->context->language->id, true)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_feature\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('Shop' == $formfield) {
                    $funct = 'getShops(true, null, false)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_shop\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('Attribute' == $formfield) {
                    $funct = 'getAttributes($this->context->language->id, false)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_attribute\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('Manufacturer' == $formfield) {
                    $funct = 'getManufacturers(false, $this->context->language->id, false)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_manufacturer\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('Supplier' == $formfield) {
                    $funct = 'getSuppliers(false, $this->context->language->id, false)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_supplier\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('PaymentModule' == $formfield) {
                    $funct = 'getInstalledPaymentModules()';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'name\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('OrderState' == $formfield) {
                    $funct = 'getOrderStates( $this->context->language->id, true)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_order_state\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('Currency' == $formfield) {
                    $funct = 'getCurrencies(false, true)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_currency\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
                if ('Language' == $formfield) {
                    $funct = 'getLanguages(true, false)';
                    $formfields .= '
                array(
                    \'type\' => \'select\',
                    \'multiple\' => true,
                    \'class\' => \'chosen\',
                    \'label\' => $this->l(\''.ucfirst(trim($formfield)).'\'),
                    \'desc\' => $this->l(\'Description of '.ucfirst(trim($formfield)).'\'),
                    \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($formfield)).'[]\',
                    \'options\' => array(
                        \'query\' => '.trim($formfield).'::'.$funct.',
                        \'id\'    => \'id_language\',
                        \'name\'  => \'name\'
                    ),
                ),';
                }
            }
        }

        if (1 == Configuration::get('RSI_GENERATOR_CATEGORY')) {
            $formfields .= '
            array(
                \'type\' => \'categories_select\',
                \'label\' => $this->l(\'Category select\'),
                \'desc\' => $this->l(\'Select a category\'),
                \'name\' => \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\',
                \'category_tree\' => $categoryTreeCol1

            ),';
        }
        $phpcontent .= $formfields;
        $phpcontent .= '
            ),
            \'submit\' => array(
                \'title\' => $this->l(\'Save\'),
            ),
        ),
        );
    }
        ';
        /* get form values */

        $phpcontent .= '
    protected function getConfigFormValues()
    {
        $fields_values = array(';
        $phpcontent .= $getFieldsValues;
        $phpcontent .= '
        );
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            '.$getFieldsValuesLang.'
        }
        return $fields_values;
    }';
        if (1 == Configuration::get('RSI_GENERATOR_CATEGORY')) {
            $cate = 'elseif ($key == \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\') {
            if ($skipcat = Tools::getValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\')) {
                Configuration::updateValue(
                    \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\',
                    implode(
                        \',\',
                        $skipcat
                    )
                );
            } elseif (Shop::getContext() == Shop::CONTEXT_SHOP || Shop::getContext() == Shop::CONTEXT_GROUP) {
                Configuration::deleteFromContext(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CATEGORY\');
            }
        }';
        } else {
            $cate = '';
        }
        if (null != Configuration::get('RSI_GENERATOR_FILE')) {
            $fil = 'elseif (strpos($key, "FILE") !== false) {
            $errors = null;
            $newfilekey = str_replace("[]", "", $key);
            $file_parts = pathinfo($_FILES[$newfilekey][\'name\']);
            if (isset($_FILES[$newfilekey]) && isset($_FILES[$newfilekey][\'tmp_name\']) && !empty($_FILES[$newfilekey][\'tmp_name\'])) {
                    if (!move_uploaded_file(
                        $_FILES[$newfilekey][\'tmp_name\'],
                        _PS_MODULE_DIR_.$this->name.\'/upload/\'.$key.\'.\'.$file_parts[\'extension\']
                    )
                    ) {
                        $errors .= $this->l(\'File upload error.\');
                    }
            }
        }';
        } else {
            $fil = '';
        }
        $checkb = null;
        if (Configuration::get('RSI_GENERATOR_CHECKFIELDS')) {
            foreach (explode(',', Configuration::get('RSI_GENERATOR_CHECKFIELDS')) as $field) {
                $checkb .= ' elseif ($key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($field)).'_1\') {
                Configuration::updateValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($field)).'_1\', Tools::getValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($field)).'_1\'));
                Configuration::updateValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($field)).'_2\', Tools::getValue(\''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_'.strtoupper(trim($field)).'_2\'));
                }';
            }
        }
        /* Post Process */
        $phpcontent .= '
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            if ($key == \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_FEATURE[]\' 
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_COUNTRY[]\' 
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_SHOP[]\' 
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_ATTRIBUTE[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_MANUFACTURER[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_SUPPLIER[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_GROUP[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CURRENCY[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_LANGUAGE[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CARRIER[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_ORDERSTATE[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_CMS[]\'
            || $key ==  \''.strtoupper(Configuration::get('RSI_GENERATOR_PREFIX')).strtoupper(Configuration::get('RSI_GENERATOR_MODULENAME')).'_EMPLOYEE[]\'
            ) {
                $newkey = str_replace("[]", "", $key);
                if ($skipcountry = Tools::getValue($newkey)) {
                    Configuration::updateValue(
                        $newkey,
                        implode(
                            \',\',
                            $skipcountry
                        )
                    );

                } elseif (Shop::getContext() == Shop::CONTEXT_SHOP || Shop::getContext() == Shop::CONTEXT_GROUP) {
                    Configuration::deleteFromContext($newkey);
                }
            } '.$checkb.' '.$cate.' '.$fil.' else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
        return (isset($errors) ? $this->displayError($errors) :"").$this->displayConfirmation(\'Configuration updated\');
    }
        ';
        fwrite($fp, $phpcontent.'
}');

        fclose($fp);

        /* Generate configuration tpl */
        $configtpl = '{*'.Configuration::get('RSI_GENERATOR_COPY').'*}'.PHP_EOL.'
            
            <script type="text/javascript">
                $(document).ready(function(){
                    var id_section = {$section_adminpage};
                    var section = ["general", "configuracion", "ayuda"];
                    var tabs = ["tab1", "tab2", "tab3"];
            
                    switch(id_section) {
                        case 2:
                            sectindex = "configuracion";
                            tabindex = "tab2";
                            break;
                        case 3:
                            sectindex = "ayuda";
                            tabindex = "tab3";
                            break;
                        case 1:
                            sectindex = "general";
                            tabindex = "tab1";
                            break;
                        default:
                            sectindex = "general";
                            tabindex = "tab1";
                            break;
                    }
            
                    loop_section(sectindex, tabindex);
            
                    //click tab event
                    $("#general_tab").click(function(){
                        loop_section("general", "tab1");
                    });
                    $("#configuracion_tab").click(function(){
                        loop_section("configuracion", "tab2");
                    });
                    $("#ayuda_tab").click(function(){
                        loop_section("ayuda", "tab3");
                    });
            
            
                    function loop_section(contentindex, tab){
                        var index;
                        for (index = 0; index < section.length; ++index) {
                            console.log(section[index]+"=="+contentindex);
            
                            if(section[index] == contentindex){
                                $("#"+contentindex).addClass("active");
                            }else{
                                $("#"+section[index]).removeClass("active");
                            }
                        }
            
                        var indextab;
                        for (indextab = 0; indextab < tabs.length; ++indextab) {
                            console.log(tabs[indextab]+"=="+tab);
            
                            if(tabs[indextab] == tab){
                                console.log("#"+tab);
            
                                $("#"+tab).addClass("active");
                            }else{
                                $("#"+tabs[indextab]).removeClass("active");
                            }
                        }
                    }
                });	
            </script>
            
            
            <ul class="nav nav-tabs" id="rsihide">				
                <li id="tab1" class="active">
                    <a href="#" id="general_tab">
                        <i class="icon-home"></i>
                          {l s=\'Dashboard\' mod=\''.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'\'}
                    </a>
                </li>
                <li id="tab2">
                    <a href="#" id="configuracion_tab">
                        <i class="icon-database"></i>
                          {l s=\'Configuration\' mod=\''.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'\'}
                    </a>
                </li>
                <li id="tab3">
                    <a href="#" id="ayuda_tab">
                        <i class="icon-cogs"></i>
                          {l s=\'Help\' mod=\''.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'\'}
                    </a>
                </li>
            
            
            </ul>
            <div class="tab-content panel">	
                <div class="tab-pane active" id="general">
                    <h1><i class="icon icon-gear"></i> {l s=\''.Configuration::get('RSI_GENERATOR_TITLE').'\' mod=\''.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'\'}</h1>
            
                    '.Configuration::get('RSI_GENERATOR_HOWTO').'

                   
                   
                </div>
                <div class="tab-pane" id="configuracion">
                    {$renderForm}
                </div>
                <div class="tab-pane" id="ayuda">
                {if $jira}
                    <div class="alert alert-info" role="alert">
                   {l s=\'If you have any question or want to made updates in the module, you can write in the related task in jira:\' mod=\''.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'\'}<br/>
                    <hr>
                   <img src="{$module_dir|escape:\'htmlall\':\'UTF-8\'}views/img/jira.png">
                   <a href="{$jira}" target="_blank">{$jira}</a>
                    </div>
                {/if}
                {if $employee and $git}
                <div class="alert alert-warning" role="alert">
                    {l s=\'The related repository in git to this task is:\' mod=\''.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'\'}<br/>
                     <hr>
                    <img src="{$module_dir|escape:\'htmlall\':\'UTF-8\'}views/img/git.png"><i class="bi bi-github"></i>	
                    <a href="{$git}" target="_blank">{$git}</a></div>
                {/if}
             '.Configuration::get('RSI_GENERATOR_COPYRIGHT').'
                </div>	
            </div>
            
            
            
            
        ';
        $ftpl = fopen('../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/views/templates/admin/configure.tpl', 'w');
        fwrite($ftpl, $configtpl);
        fclose($ftpl);
        $dirPath = '../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'/';
        $zipPath = '../modules/rsi_generator/generated/'.Configuration::get('RSI_GENERATOR_PREFIX').strtolower(Configuration::get('RSI_GENERATOR_MODULENAME')).'.zip';
        $zip = $this->zipDir($dirPath, $zipPath);
        $output = null;
        if ($zip) {
            $output .= $this->displayConfirmation('ZIP archive created successfully, Download here:').'<div class="alert alert-warning" role="alert">
            <a href="'.$zipPath.'" class="btn btn-default "><i class="icon-download"></i> DOWNLOAD</a></div>';
        } else {
            $output .= $this->displayError('Failed to create ZIP.');
        }

        return $output;
    }

    public static function zipDir($sourcePath, $outZipPath)
    {
        $rootPath = realpath($sourcePath);

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($outZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            } else {
                if (false !== $relativePath) {
                    $zip->addEmptyDir($relativePath);
                }
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();

        return true;
    }

    private static function dirToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ('.' != $f && '..' != $f && $f != basename(__FILE__)) {
                $filePath = "$folder/$f";
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    $zipFile->addEmptyDir($localPath);
                    self::dirToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }
}