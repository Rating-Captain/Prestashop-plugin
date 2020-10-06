<?php
/**
 * 2007-2015 PrestaShop
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
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_'))
	exit;
require (__DIR__.'/external/RatingCaptain_client.php');
class Ratingcaptain extends Module
{
	public function __construct()
	{
		$this->name = 'ratingcaptain';
		$this->tab = 'ratingcaptain';
		$this->version = '2.3.4';
		$this->author = 'Mateusz Bielak';
		$this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
		parent::__construct();

		$this->need_instance = 1;
		$this->displayName = $this->l('Rating Captain');
		$this->description = $this->l('Integrate your e-commerce with RatingCaptain system');
		$this->confirmUninstall = $this->l('Are you sure you want uninstall ratingcaptain? your orders will not be more synchronized...');

		/*if (version_compare(_PS_VERSION_, '1.5', '<'))
			require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');*/

	}

	public function install()
	{
 /*       $this->registerHook('actionOrderStatusPostUpdate');
        $this->registerHook('orderConfirmation');*/
	    if(!parent::install() || !$this->registerHook('displayFooterAfter') || $this->registerHook('orderConfirmation')) return true;
	    return true;
	}
    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit'.$this->name)) {
            $myModuleName = strval(Tools::getValue('Ratingcaptain_api_key'));
            if (
                !$myModuleName ||
                empty($myModuleName) ||
                !Validate::isGenericName($myModuleName)
            ) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('Ratingcaptain_api_key', $myModuleName);
                if(Tools::getValue('Ratingcaptain_products_') == 'on'){
                    Configuration::updateValue('Ratingcaptain_products', true);
                }else Configuration::updateValue('Ratingcaptain_products', false);
                Configuration::updateValue('Ratingcaptain_rates_placeholder', Tools::getValue('Ratingcaptain_rates_placeholder'));
                Configuration::updateValue('Ratingcaptain_info_placeholder', Tools::getValue('Ratingcaptain_info_placeholder'));
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }
    public function hookActionOrderStatusPostUpdate($order){
	    $order = new Order($order['id_order']);
	    $this->sendToRating($order);
	    return true;
    }
    protected function sendToRating($order){
        if($api = Configuration::get('Ratingcaptain_api_key')){
            if($order){
                $ratingcaptain = new RatingCaptain_client($api);
                if(Configuration::get('Ratingcaptain_products')){
                    $products = $order->getProducts();
                    foreach ($products as $product){
                        $product = new Product($product['product_id']);
                        $ratingcaptain->addProduct($product->id, $product->name, Product::getPriceStatic($product->id), Product::getCover($product->id), $product->description);
                    }
                }
                $id_customer=$order->id_customer;
                $customer= new Customer((int)$id_customer);
                $ord = ["external_id" => $order->id, "email" => $customer->email, 'name' => $customer->firstname, 'surname' => $customer->lastname, 'send_date' => Date('Y-m-d H:i:s', strtotime('+5 days'))];
                $ratingcaptain->send($ord);
            }
        }
    }
    public function hookOrderConfirmation($params = null){
	    $order = $params['order'];
	    $this->sendToRating($order);
        return true;
    }
    public function hookDisplayFooterAfter($params){
	    $to_return = "";
	    if($rates_placeholder = Configuration::get('Ratingcaptain_rates_placeholder')){
	        $to_return .= "<script>var RatingCaptain_rates_placeholder = '".$rates_placeholder."';</script>";
        }
	    if($info_placeholder = Configuration::get('Ratingcaptain_info_placeholder')){
            $to_return .= "<script>var RatingCaptain_info_placeholder = '".$info_placeholder."';</script>";
        }
	    if($api_key = Configuration::get('Ratingcaptain_api_key')){
	        $to_return .= "<script type='text/javascript' src='https://ratingcaptain.com/api/js/".$api_key."'></script>";
        }
	    return $to_return;
    }

    public function displayForm()
    {

        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Ratingcaptain Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Ratingcaptain api key'),
                    'name' => 'Ratingcaptain_api_key',
                    'size' => 300,
                    'required' => true
                ],
                [
                    'type' => 'checkbox',
                    'label' => $this->l('Send products info'),
                    'name' => 'Ratingcaptain_products',
                    'values' => array(
                        'query' =>1,
                        'id' => 'id',
                        'name' => 'name',
                        'value' => '1'
                    ),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('element ID on website that will hold Ratingcaptain rates'),
                    'name' => 'Ratingcaptain_rates_placeholder',
                    'size' => 300,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('element ID on website that will hold Ratingcaptain info'),
                    'name' => 'Ratingcaptain_info_placeholder',
                    'size' => 300,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->fields_value['Ratingcaptain_api_key'] = Configuration::get('Ratingcaptain_api_key');
        $helper->fields_value['Ratingcaptain_products_'] = Configuration::get('Ratingcaptain_products');
        $helper->fields_value['Ratingcaptain_rates_placeholder'] = Configuration::get('Ratingcaptain_rates_placeholder');
        $helper->fields_value['Ratingcaptain_info_placeholder'] = Configuration::get('Ratingcaptain_info_placeholder');

        return $helper->generateForm($fieldsForm);
    }
}
