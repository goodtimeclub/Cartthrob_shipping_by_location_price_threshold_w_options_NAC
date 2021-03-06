<?php if ( ! defined('CARTTHROB_PATH')) Cartthrob_core::core_error('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * NAC gift cards shipping options plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		Dan Copeland - National Arts Centre / Centre national des Arts
 * @link		http://nac-cna.ca
 */

class Cartthrob_shipping_by_location_price_threshold_w_options_NAC extends Cartthrob_shipping
{
	public $title = 'NAC shipping options';
	public $classname = __CLASS__;
	public $note = 'price_threshold_overview';
	public $settings = array(
			array(
				'name' => 'set_shipping_cost_by',
				'short_name' => 'mode',
				'default' => 'rate',
				'type' => 'radio',
				'options' => array(
					'price' => 'rate_amount',
					'rate' => 'rate_amount_times_cart_total'
				)
			),
			array(
				'name' => 'primary_location_field',
				'short_name' => 'location_field',
				'type' => 'select',
				'default'	=> 'country_code',
				'options' => array(
					'zip' => 'zip',
					'state'	=> 'state', 
					'region' => 'Region',
					'country_code' => 'settings_country_code',
					'shipping_zip' => 'shipping_zip',
					'shipping_state' => 'shipping_state',
					'shipping_region' => 'shipping_region', 
					'shipping_country_code' => 'settings_shipping_country_code'
				)
			),
			array(
				'name' => 'backup_location_field',
				'short_name' => 'backup_location_field',
				'type' => 'select',
				'default'	=> 'country_code',
				'options' => array(
					'zip' => 'zip',
					'state'	=> 'state', 
					'region' => 'Region',
					'country_code' => 'settings_country_code',
					'shipping_zip' => 'shipping_zip',
					'shipping_state' => 'shipping_state',
					'shipping_region' => 'shipping_region', 
					'shipping_country_code' => 'settings_shipping_country_code'
				)
			),
			array(
				'name' => 'thresholds',
				'short_name' => 'thresholds',
				'type' => 'matrix',
				'settings' => array(
					array(
						'name'			=>	'location_threshold',
						'short_name'	=>	'location',
						'type'			=>	'text',	
					),
					array(
						'name' => 'rate',
						'short_name' => 'default_rate',
						'note' => 'rate_example',
						'type' => 'text'
					),
					array(
						'name' => 'express',
						'short_name' => 'express_rate',
 						'type' => 'text'
					),
					array(
						'name' => 'price_threshold',
						'short_name' => 'threshold',
						'note' => 'price_threshold_example',
						'type' => 'text'
					)
				)
			)
		);
	public $rates = array(); 
	public $rate_titles = array(); 
	public $default_shipping_option = "default_rate"; 
	public $cost = 0; 
	public $default_rates = array(
		'default_rate'		=> 0,
		'express_rate'		=> 0,
		//'overnight_rate'	=> 0,
		); 
	public function initialize()
	{
		// @TODO language
		$this->rate_titles = array(
				'default_rate'	=> 'Standard',
				'express_rate'	=> 'Express',
				'overnight_rate'	=> 'Overnight',
			);
		if ($this->core->cart->count() <= 0 || $this->core->cart->shippable_subtotal() <= 0)
		{
			return 0;
		}
		$this->shipping_option = ($this->core->cart->shipping_info('shipping_option')) ? $this->core->cart->shipping_info('shipping_option') : $this->default_shipping_option();

		switch ($this->shipping_option)
		{
			case "default_rate":
				$rate = "default_rate";
				break;
			case "express_rate": 
				$rate = "express_rate"; 
				break;
		}
  		$customer_info = $this->core->cart->customer_info(); 

		$location_field = $this->plugin_settings('location_field', 'shipping_country_code');
		$backup_location_field = $this->plugin_settings('backup_location_field', 'country_code');
		$location = '';
		
		if ( ! empty($customer_info[$location_field]))
		{
			$location = $customer_info[$location_field];
		}
		else if ( ! empty($customer_info[$backup_location_field]))
		{
			$location = $customer_info[$backup_location_field];
		}
		
		$shipping = 0;
		$price = $this->core->cart->shippable_subtotal();

		$priced = FALSE;
		$last_rate = ''; 
		
		$total_items = $this->core->cart->count_all(array('no_shipping' => FALSE));
		
		foreach ($this->plugin_settings('thresholds', array()) as $threshold_setting)
		{
			// the last rates listed. 
			$default_rates["default_rate"] 	= $threshold_setting["default_rate"];
			$default_rates["express_rate"] 	= $threshold_setting["express_rate"];
			
			$location_array	= preg_split('/\s*,\s*/', trim($threshold_setting['location']));
			
			if (in_array($location, $location_array))
			{
 				$this->rates["default_rate"] 	= $threshold_setting["default_rate"];
 				$this->rates["express_rate"] 	= $threshold_setting["express_rate"];
				
				if ($total_items > $threshold_setting['threshold'])
				{
					$last_rate = $threshold_setting[$rate];
					continue;
				}
				else
				{
					$shipping = ($this->plugin_settings('mode') == 'rate') ? $price * $threshold_setting[$rate] : $threshold_setting[$rate];

					$priced = TRUE;

					break;
				}
				$last_rate = $threshold_setting[$rate];
			}
			elseif (in_array('GLOBAL',$location_array)) 
			{
				
				if ($total_items > $threshold_setting['threshold'])
				{
					$this->rates["default_rate"] 	= $threshold_setting["default_rate"];
	 				$this->rates["express_rate"] 	= $threshold_setting["express_rate"];
	 				
					$last_rate = $threshold_setting[$rate];
					continue;
				}
				else
				{
					$shipping = ($this->plugin_settings('mode') == 'rate') ? $price * $threshold_setting[$rate] : $threshold_setting[$rate];

					$this->rates["default_rate"] 	= $threshold_setting["default_rate"];
	 				$this->rates["express_rate"] 	= $threshold_setting["express_rate"];
	 				
					$priced = TRUE;

					break;
				}
				$last_rate = $threshold_setting[$rate];
			}
		}

		if ( ! $priced)
		{
			$shipping = ($this->plugin_settings('mode') == 'rate') ? $price * $last_rate : $last_rate;
			
			$this->rates = $this->default_rates; 
		}
 		$this->cost= $shipping;
 	}
	public function get_shipping()
	{
		return $this->cost; 
	}
	// END
	public function default_shipping_option()
	{
		return $this->default_shipping_option;
	}
	public function plugin_shipping_options()
	{
		$options = array();
		
		foreach ($this->rates as $rate_short_name => $price)
		{
			$options[] = array(
				'rate_short_name' => $rate_short_name,
				'price' => $price,
				'rate_price' => $price,
				'rate_title' => $this->rate_titles[$rate_short_name],
			);
		}
		
		return $options;
	}
}//END CLASS
