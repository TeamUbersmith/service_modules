<?php

/**
 * My Service Module
 *
 * You will want to change my_service_module to something a little more
 * descriptive.
 *
 * When complete, place your finished device module into
 * include/service_modules/
 *
 * @package ubersmith_customizations
 */

/**
 * My Service Module Class
 *
 * @package ubersmith_customizations
 */
class my_service_module extends service_module
{
	/**
	 * The name of the service module. This title will be shown in the
	 * 'Service Plans' section of 'Setup & Admin', as well as within the
	 * client services in the Client Manager.
	 *
	 * @return string
	 */
	public static function title()
	{
		return uber_i18n('My Service Module');
	}

	/**
	 * The 'get_metadata_config()' will automatically create custom data
	 * fields if they do not exist when the service module is installed.
	 * 'type' can be 'text' or 'select'. If using 'select', pass an 'options'
	 * key with an array of your values to be presented in the select box.
	 * See below for a (commented out) example.
	 *
	 * @return array
	 */
	public function get_metadata_config()
	{
		return array(
			'meta_type'  => 'client',
			'items'      => array(
				'my_special_field' => array(
					'prefix'      => uber_i18n('My Special Field'),
					'type'        => 'text',
					'size'        => 6,
					'default_val' => '',
					// 'options'     => array(
					// 	'special' => 'Special',
					// 	'notso'   => 'Not So Special',
					// ),
				)
			),
		);
	}

	/**
	 * The 'summary' function allows you to display some useful information
	 * related to your service module on a per client service basis. This
	 * will be displayed in the lower right hand corner of the service's
	 * page when viewed in the Client Manager.
	 *
	 * @return bool
	 */
	public function summary()
	{
		echo '<div>Hello, and welcome to my service module.</div>';

		$sky = $this->config('my_option');

		if (empty($sky)) {
			$sky = 'dunno!';
		}

		echo '
		<div>
			<span>Is the sky blue?: ' . h($sky) . '</span>
		</div>';

		$service = $this->service;

		echo '<pre>';

		var_dump($service);

		echo '</pre>';

		return true;
	}

	/**
	 * This function returns an array of configuration options that will be
	 * displayed when the module is configured for a service plan. You can
	 * add as many configuration items as you like. Retrieval of the
	 * configuration data is shown in the summary() function above.
	 *
	 * @return array
	 */
	public function config_items()
	{
		return array(
			'my_textfield' => array(
				'label'  => uber_i18n('A Text Field'),
				'type'   => 'text',
				'size'   => '20',
				'default'=> '',
			),
			'my_option' => array(
				'label'   => uber_i18n('Is the sky blue?'),
				'type'    => 'select',
				'options' => array(
					'yes' => uber_i18n('Yes'),
					'no' => uber_i18n('No'),
				),
				'default' => 'false',
			),
		);
	}

	/*
	 * These supporting functions can return 'true' when successful, or a
	 * PEAR error object on failure. As an example:
	 *
	 * return PEAR::raiseError('Something terrible happened, send for help',1);
	 */

	/**
	 * The install function directs the service module to perform tasks upon
	 * creation.
	 *
	 * This might include creating metadata items, reaching out to a remote
	 * service, or any number of tasks that you would like to perform upon
	 * initial setup.
	 *
	 * @return bool|PEAR_Error
	 */
	public function install()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is first
	 * created.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onstart()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is
	 * edited/updated.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafteredit()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is
	 * changed to the 'Suspended' state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforesuspend()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is
	 * changed to the 'Suspended' state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onaftersuspend()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is
	 * changed from the 'Suspended' state to any other state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforeunsuspend()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is
	 * changed from the 'Suspended' state to any other state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafterunsuspend()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is
	 * changed  to the 'Cancelled' state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforecancel()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is
	 * changed to the 'Cancelled' state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onaftercancel()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is
	 * changed from the 'Cancelled' state to any other state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforeuncancel()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is
	 * changed from the 'Cancelled' state to any other state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafteruncancel()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is
	 * renewed.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforerenew()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is
	 * renewed.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafterrenew()
	{
		return true;
	}

	/**
	 * This function is called immediately after payment is received for the
	 * related service.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafterpayment()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is
	 * created.
	 *
	 * @return bool
	 */
	public function onbeforecreate()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is
	 * created.
	 *
	 * @return bool
	 */
	public function onaftercreate()
	{
		return true;
	}

	/**
	 * This function is called immediately after an invoice is generated for
	 * the related service
	 *
	 * @return bool
	 */
	public function onafterinvoice()
	{
		return true;
	}
}
