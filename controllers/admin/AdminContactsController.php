<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AdminContactsControllerCore
 *
 * @since 1.0.0
 */
class AdminContactsControllerCore extends AdminController
{
    /**
     * AdminContactsControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'contact';
        $this->className = 'Contact';
        $this->lang = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'id_contact'  => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'        => ['title' => $this->l('Title')],
            'email'       => ['title' => $this->l('Email address')],
            'description' => ['title' => $this->l('Description')],
            'active'      => [
                'title'    => $this->l('Active'),
                'align'    => 'text-center',
                'type'     => 'bool',
                'callback' => 'printContactActiveIcon',
                'orderby'  => false,
                'class'    => 'fixed-width-sm',
            ],
        ];

        parent::__construct();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Contacts'),
                'icon'  => 'icon-envelope-alt',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Title'),
                    'name'     => 'name',
                    'required' => true,
                    'lang'     => true,
                    'col'      => 4,
                    'hint'     => $this->l('Contact name (e.g. Customer Support).'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Email address'),
                    'name'     => 'email',
                    'required' => false,
                    'col'      => 4,
                    'hint'     => $this->l('Emails will be sent to this address.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Save messages?'),
                    'name'     => 'customer_service',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'hint'     => $this->l('If enabled, all messages will be saved in the "Customer Service" page under the "Customer" menu.'),
                    'values'   => [
                        [
                            'id'    => 'customer_service_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'customer_service_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('Description'),
                    'name'     => 'description',
                    'required' => false,
                    'lang'     => true,
                    'col'      => 6,
                    'hint'     => $this->l('Further information regarding this contact.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Is contact active?'),
                    'name'     => 'active',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        // Set 'Is contact active' switch to ON if this is the create form
        if (!$this->id_object) {
            $this->fields_value = [
                'active' => true,
            ];
        }

        return parent::renderForm();
    }

    /**
     * Toggle contact active flag
     */
    public function processChangeContactActiveVal()
    {
        $contact = new Contact($this->id_object);
        if (!Validate::isLoadedObject($contact)) {
            $this->errors[] = Tools::displayError('An error occurred while updating this contact.');
        }
        $update = Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'contact` SET active = '.($contact->active ? 0 : 1).' WHERE `id_contact` = '.(int)$contact->id);
        if (!$update) {
            $this->errors[] = Tools::displayError('An error occurred while updating this contact.');
        }
        Tools::clearSmartyCache();
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Print enable / disable icon for is contact active option
     *
     * @param $id_contact integer Contact ID
     * @param $tr array Row data
     * @return string HTML link and icon
     */
    public function printContactActiveIcon($id_contact, $tr)
    {
        $contact = new Contact($tr['id_contact']);
        if (!Validate::isLoadedObject($contact)) {
            return;
        }

        return '<a class="list-action-enable'.($contact->active ? ' action-enabled' : ' action-disabled').'" href="index.php?controller=AdminContacts&amp;id_contact='.(int)$contact->id.'&amp;changeContactActiveVal&amp;token='.Tools::getAdminTokenLite('AdminContacts').'">
				'.($contact->active ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').
            '</a>';
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        $this->initToolbar();
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_contact'] = [
                'href' => static::$currentIndex.'&addcontact&token='.$this->token,
                'desc' => $this->l('Add new contact', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function initProcess()
    {
        parent::initProcess();

        $this->id_object = Tools::getValue('id_'.$this->table);

        if (Tools::isSubmit('changeContactActiveVal') && $this->id_object) {
            $this->action = 'change_contact_active_val';
        }
    }

    /**
     * @param int  $idLang
     * @param null $orderBy
     * @param null $orderWay
     * @param int  $start
     * @param null $limit
     * @param bool $idLangShop
     *
     * @since 1.0.4
     */
    public function getList(
        $idLang,
        $orderBy = null,
        $orderWay = null,
        $start = 0,
        $limit = null,
        $idLangShop = false
    ) {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        foreach ($this->_list as &$row) {
            $row['email'] = Tools::convertEmailFromIdn($row['email']);
        }
    }

    /**
     * Return the list of fields value
     *
     * @param ObjectModel $obj Object
     *
     * @return array
     *
     * @since 1.0.4
     */
    public function getFieldsValue($obj)
    {
        $fieldsValue = parent::getFieldsValue($obj);
        $fieldsValue['email'] = Tools::convertEmailFromIdn($fieldsValue['email']);

        return $fieldsValue;
    }
}
