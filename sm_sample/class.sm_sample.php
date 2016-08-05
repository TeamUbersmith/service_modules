<?php
/**
 * My Service Module
 */

/**
 * My Service Module Class
 *
 * Please change the class name from "sm_sample" to something descriptive, 
 * but retain the "sm_" prefix to ensure that it's detected by Ubersmith. 
 * Please review the existing modules there and make sure you don't choose 
 * a filename that has already been used. This file will need to be named 
 * whatever this class is called with a ".php" extension.
 *
 * When you're ready to use your module, simply place this file in the 
 * "include/service_modules/" subdirectory of your Ubersmith base directory.
 */
class sm_sample extends service_module
{
	public static function title()
	{
		return uber_i18n('Usage-Based Power Billing');
	}
	
	/**
	 * This method returns an array of configuration options that will be
	 * displayed when the module is configured for a service plan. You can
	 * add as many configuration items as you like. Retrieval of the
	 * configuration data is shown in the summary() function above.
	 *
	 * @return array
	 */
	public function config_items()
	{
		return array(
			'precision' => array(
				'label'   => uber_i18n('Overage Precision') .':',
				'type'    => 'select',
				'options' => array(0=>'0',1=>'1',2=>'2'),
				'default' => '0',
			),
		);
	}
	
	/**
	 * The 'get_metadata_config()' method will automatically create custom data
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
			array(
				'group_name' => uber_i18n('Usage-Based Power Billing'),
				'items'      => array(
					'usage_power_circuit_id' => array(
						'prefix'      => uber_i18n('Circuit ID'),
						'type'        => 'text',
						'size'        => 30,
						'default_val' => '',
					),
					'usage_power_included' => array(
						'prefix'      => uber_i18n('Commit') .' ('. uber_i18n('kWh') .')',
						'type'        => 'text',
						'size'        => 30,
						'default_val' => '',
					),
					'usage_power_overage_rate' => array(
						'prefix'      => uber_i18n('Overage Rate') .' ('. uber_i18n('Cost/kWh') .')',
						'type'        => 'text',
						'size'        => 30,
						'default_val' => '',
					),
/*
					'select_example' => array(
						'prefix'      => 'Select Example',
						'type'        => 'select',
						'size'        => 30,
						'default_val' => '',
						'options'     => array(
						'special' => uber_i18n('Special'),
						'notso'   => uber_i18n('Not So Special'),
					),
*/
				),
			),
		);
	}
	
	/**
	 * The install function directs the service module to perform tasks upon
	 * creation.
	 *
	 * This might include creating metadata items, reaching out to a remote
	 * service, or any number of tasks that you would like to perform upon
	 * initial setup.
	 *
	 * @return bool|PEAR_Error
	 *
	 */
	 
	/** Deprecated by internal install function
	public function install()
	{
		return parent::install();
	}
	*/

	/**
	 * The 'summary' function allows you to display some useful information
	 * related to your service module on a per client service basis. This
	 * will be displayed in the lower portion of the service's
	 * page when viewed in the Client Manager.
	 *
	 * @return bool
	 */
	public function summary($request = array())
	{
		$report = $this->_summary($request);
		if ($report === true) {
			return true;
		}
		
		if (empty($request['usage_power_period'])) {
			$request['usage_power_period'] = 'curr';
		}
		
		$periods = array(
			'curr' => uber_i18n('Current'),
			'prev' => uber_i18n('Previous'),
		);
		
		print '
			<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom:5px;">
				<tr>';
		
		if (!PEAR::isError($report)) {
			print '
					<td style="padding-left:4px;">
						'. gui::popup_link('popup_module_view.php?packid='. u($this->service['packid']) .'&service_module='. u($this->id()) .'&view=details&usage_power_period='. u($request['usage_power_period']),uber_i18n('Details'),'popup_module_view','width=810,height=640') .'
					</td>';
		}
		
		print '
					<td align="right">'.uber_i18n('Renewal Period') .': '. gui::input_select('usage_power_period',$periods,$request,array('onchange'=>'this.form.submit();')) .'</td>
				</tr>
			</table>';
		
		if (PEAR::isError($report)) {
			print display_warning(uber_i18n('Notice'),$report->getMessage());
			return;
		}
		
		print $this->print_summary($report);
		
		return true;
	}
	
	public function summary_api($request = array())
	{
		return $this->_summary($request);
	}
	
	function _summary($request = array(),$metadata = null)
	{
		if (empty($request['now'])) {
			$request['now'] = time();
		}
		
		if (empty($request['usage_power_period'])) {
			$request['usage_power_period'] = 'curr';
		}
		
		// Grab service metadata (if necessary)
		if (!isset($metadata)) {
			$metadata = $this->metadata();
			if (PEAR::isError($metadata)) {
				return $metadata;
			}
		}
		
		$end_time = $request['now'];
		
		$service = $this->service;
		
		if (empty($service['post_renew'])) {
			return PEAR::raiseError(uber_i18n('Usage-Based Power Billing services must be set to post renew.'),1);
		}
		if (empty($service['period'])) {
			return PEAR::raiseError(uber_i18n('Usage-Based Power Billing services must have a period.'),1);
		}
		
		if ($request['usage_power_period'] == 'prev') {
			$start_time = $service['lastrenew'];
			$end_time = $service['renewdate'];
		} else {
			$period = $service['period'];
			
			$start_time = $service['renewdate'];
			$t = getdate($start_time);
			$end_time = mktime(0,0,0,$t['mon'] + $period,$t['mday'],$t['year']);
		}
		
		// don't bill past the end date of the service
		if (!empty($service['end']) && $service['end'] < $end_time) {
			$end_time = $service['end'];
		}
		
		// make sure we don't start earlier than the start date of the service
		$start_time = max($start_time,$service['start']);
		
		if ($end_time > $request['now']) {
			$end_time = $request['now'];
		}
		
		$request['end']   = $end_time;
		$request['start'] = $start_time;
		
		return $this->_report($request,$metadata);
	}
	
	function print_summary($report)
	{
		if (!empty($report['included'])) {
			$rate_label = uber_i18n('Overage Rate') .':';
			$overage_label = uber_i18n('Overage');
		} else {
			$rate_label = uber_i18n('Usage Rate') .':';
			$overage_label = uber_i18n('Usage');
		}
		
		$output = '
			<table width="100%" border="1" cellpadding="4" cellspacing="0" style="background: #ffffff;border-collapse:collapse;border:1px solid #999999;">
				<tr>
					<td width="10%"><b>'. uber_i18n('Period') .'</b></td>
					<td width="15%">'. uber_i18n('From:','time') .'</td>
					<td width="30%">'. hd($report['report']['ts']['start'],$_SESSION['dateformat'] .' g:i A') .'</td>
					<td width="15%">'. uber_i18n('To:','time') .'</td>
					<td width="30%">'. hd($report['report']['ts']['end'],$_SESSION['dateformat'] .' g:i A') .'</td>
				</tr>
				<tr>
					<td><b>'. uber_i18n('Total') .'</b></td>
					<td>'. uber_i18n('Usage') .':</td>
					<td colspan="3">'. $report['report']['kwh'] .' '. uber_i18n('kWh') .'</td>
				</tr>
				<tr>';
				
		if (!empty($report['included'])) {
			$output .= '
					<td colspan="2"><b>'. uber_i18n('Included') .'</b></td>
					<td>'. $report['included'] .' '. $report['unit'] .'</td>';
		} else {
			$output .= '
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>';
		}
		
		$output .= '
					<td><b>'. $rate_label .'</b></td>
					<td>'. currency($report['overage_rate']) .' / '. $report['unit'] .'</td>
				</tr>';
		
		if (empty($report['overage'])) {
			$output .= '
				<tr>
					<td width="100%" colspan="5" style="color:green;">'. uber_i18n('Under included usage') .'</td>
				</tr>';
		} else {
			$output .= '
				<tr>
					<td width="10%" style="color:blue;"><b>'. $overage_label .'</b></td>
					<td width="15%"><b>'.uber_i18n('Amount') .':</b></td><td width="30%">'. $report['overage'] .' '. $report['unit'] .'</td>
					<td width="15%"><b>'.uber_i18n('Amount Due') .':</b></td><td width="30%">'. currency($report['amount_due']) .'</td>
				</tr>';
		}
		
		$output .= '
			</table>';
		
		return $output;
	}
	
	function print_details($report)
	{
		$output = '';
		
		return $output;
	}
	
	function invoicedetail($request = array())
	{
		// called for a parent description line item
		if (isset($request['desserv_only'])) {
			return true;
		}
		
		$request['start'] = $request['date_range_start'];
		$request['end']   = $request['date_range_end'];
		
		$report = $this->_report($request);
		if (PEAR::isError($report)) {
			return $report;
		}
		
		$output = $this->print_summary($report);
		
		$output .= '
		<p><strong>'. uber_i18n('Details') .'</strong></p>';
		
		$output .= $this->print_details($report);
		
		return $output;
	}
	
	// This method writes details to the invoice PDF
	function pdfinvoicedetail($pdf,$request,$inv_vals)
	{
		if (!empty($request['details'][$this->id()]['output'])) {
			$pdf->writeHTML($request['details'][$this->id()]['output']);
		}
		
		return $pdf->GetY();
	}
	
	function view($request = array())
	{
		// Grab service metadata
		$metadata = $this->metadata();
		if (PEAR::isError($metadata)) {
			return $metadata;
		}
		
		if (isset($request['view']) && $request['view'] == 'details') {
			$report = $this->_summary($request,$metadata);
			if ($report === true) {
				return PEAR::raiseError(uber_i18nf('%s not configured',uber_i18n('Usage-Based Power Billing')),1);
			}
			
			$popup = new Popup(uber_i18n('Usage-Based Power Billing'));
			
			$popup->addHidden('packid');
			$popup->addHidden('service_module');
			$popup->addHidden('view');
			
			$tab =& $popup->addTab(uber_i18n('Details'));
			
			$periods = array(
				'curr' => uber_i18n('Current'),
				'prev' => uber_i18n('Previous'),
			);
			
			$output = '
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom:5px;">
					<tr>
						<td><strong>'. uber_i18n('Summary') .'</strong></td>
						<td align="right">'.uber_i18n('Renewal Period') .': '. gui::input_select('usage_power_period',$periods,$request,array('onchange'=>'this.form.submit();')) .'</td>
					</tr>
				</table>';
			
			$output .= $this->print_summary($report);
			
			$output .= '
			<div style="margin:10px 0 5px 0;"><strong>'. uber_i18n('Details') .'</strong></div>';
			
			$output .= $this->print_details($report);
			
			$tab->setContent($output);
			
			$popup->display();
		}
		
		return true;
	}
	
	function edit($request = array())
	{
		// Grab service metadata
		$metadata = $this->metadata();
		if (PEAR::isError($metadata)) {
			return $metadata;
		}
		
		$output = '';
		
		$info = array(
			'circuit_id' => 0,
			'included'     => 0,
			'overage_rate' => 0,
		);
		
		foreach ($info as $key => $value) {
			if (isset($request[$key])) {
				$info[$key] = $request[$key];
			} elseif (isset($metadata['usage_power_'. $key])) {
				$info[$key] = $metadata['usage_power_'. $key];
			}
		}
		
		while (!empty($request['editclick'])) {
			$req = array(
				'packid' => $this->service['packid'],
			);
			foreach ($info as $key => $value) {
				$req['meta_usage_power_'. $key] = $value;
			}
			
			$result = edit_pack($req);
			if (!$result) {
				$output .= display_warning(uber_i18n('Error'),$_SESSION['ERROR']->get('edit_pack'));
				break;
			}
			
			$output .= display_success(uber_i18n('Success'),uber_i18n('Service Updated'));
			$GLOBALS['javascript'] .= '
			window.opener.location.href='. j('client_service_details.php?packid='. u($this->service['packid']) .'&success='. u(uber_i18n('Service Updated'))) .';
			window.close();';
			break;
		}
		
		$output .= '
			<table border="0" width="100%" cellpadding="4" cellspacing="0" style="margin:10px 0;">
				<tr>
					<td width="40%" align="right">'. gui::label('circuit_id',uber_i18n('Circuit ID')) .'</td>
					<td>'. gui::input_text('circuit_id',$info) .'</td>
				</tr>
				<tr>
					<td align="right">'. gui::label('included',uber_i18n('Commit')) .'</td>
					<td>'. gui::input_text('included',$info) .' '. uber_i18n('kWh') .'</td>
				</tr>
				<tr>
					<td align="right">'. gui::label('overage_rate',uber_i18n('Overage Rate')) .'</td>
					<td>'. gui::input_text('overage_rate',$info) .' '. currency(null) .'/'. uber_i18n('kWh') .'</td>
				</tr>
			</table>';
		
		return $output;
	}
	
	function _report($request,$metadata = null)
	{
		// Grab service metadata (if necessary)
		if (!isset($metadata)) {
			$metadata = $this->metadata();
			if (PEAR::isError($metadata)) {
				return $metadata;
			}
		}
		
		$report = array(
			'kwh'  => 0,
			'cost' => 0,
			'ts' => array(
				'start' => $request['start'],
				'end'   => $request['end'],
			),
		);
		if (!empty($metadata['usage_power_circuit_id'])) {
			// This query assumes data that looks like:
			// kwh: 623.9700
			// circuit_id: RPP-6-3C_11
			// bill_month: 2013-10-01
			$query = 'SELECT `kwh` FROM `usage` WHERE `circuit_id`=? AND `bill_month`>=? AND `bill_month`<=?';
			$args = array(
				$metadata['usage_power_circuit_id'],
				date('Y-m-d',$request['start']),
				date('Y-m-d',$request['end']),
			);
			
			// Get specific database settings
			$db = $this->get_database();
			if (PEAR::isError($db)) {
				return PEAR::raiseError(uber_i18n_en('Error loading database settings'),1);
			}
			
			$row = $db->getRow($query,$args);
			if (PEAR::isError($row)) {
				trigger_error('sql query failed');
				return PEAR::raiseError(uber_i18nf('Error retrieving %s',uber_i18n('Usage-Based Power Billing') .' report data'),1);
			}
			
			if (!empty($row['kwh'])) {
				$report['kwh'] = $row['kwh'];
			}
		}
		
		$rawoverage = $overage = $amount_due = 0;
		$rawfactor = 1;
		
		$included = 0;
		$included_details = array();
		
		if (empty($metadata['usage_power_included'])) {
			// do nothing
		} elseif ($metadata['usage_power_included'] == '*') {
			$services = list_packs(array(
				'clientid'         => $this->service['clientid'],
				'pack_type_select' => 1,
				'parentpack'       => null,
			));
			foreach ($services as $service) {
				$s_metadata = service_metadata($service);
				if (empty($s_metadata['usage_power_included']) || !is_numeric($s_metadata['usage_power_included'])) {
					continue;
				}
				
				$included += $s_metadata['usage_power_included'];
				$included_details[$service['packid']] = array(
					'desserv'  => $service['desserv'],
					'included' => $s_metadata['usage_power_included'],
				);
			}
		} else {
			if (!is_numeric($metadata['usage_power_included'])) {
				return PEAR::raiseError(uber_i18nf('Invalid %s specified','commit'),1);
			}
			
			$included += $metadata['usage_power_included'];
		}
		
		$included = round($included);
		
		$unit = '';
		
		// calculate usage
		$usage = $report['kwh'];
		$unit  = uber_i18n('kWh');
		$label = uber_i18n('Kilowatt Hours');
		
		if (!isset($metadata['usage_power_overage_rate']) || !is_numeric($metadata['usage_power_overage_rate'])) {
			return PEAR::raiseError(uber_i18nf('Invalid %s specified','overage rate'),1);
		}
		
		// over the limit
		if ($usage > $included) {
			$rawoverage = $usage - $included;
			
			$overage = $this->roundup($rawoverage,$this->config('precision'));
			
			$amount_due = $overage * $metadata['usage_power_overage_rate'];
		}
		
		return array(
			'report'        => $report,
			'included'      => $included,
			'overage'       => $overage,
			'overage_rate'  => $metadata['usage_power_overage_rate'],
			'unit'          => $unit,
			'amount_due'    => $amount_due,
		);
	}
	
	function roundup($num,$digits = 0)
	{
		if (empty($digits)) {
			return ceil($num);
		}
		
		$fact = pow(10,$digits);
		
		return round(ceil($num * $fact) / $fact,$digits);
	}
	
	// This method
	function get_database()
	{
		/**
		 * The database connection should be configured in Ubersmith's config.ini.php 
		 * file, like so:
		 * 
		 * [usage]
		 * usage_data_dsn = mysql://{USERNAME}:{PASSWORD}@{HOST}/{DATABASE_NAME}
		 * 
		 * Please note that you should not delete other entries you find in that file.
		 */
		// 
		// Usage database connection
		$options = array(
			'persistent' => false,
			'autofree'   => true,
		);
		
		static $usage_data_dsn = '';
		
		if (empty($usage_data_dsn)) {
			$ini = uber_ini();
			if (PEAR::isError($ini)) {
				return $ini;
			}
			
			if (empty($ini['usage']) || empty($ini['usage']['usage_data_dsn'])) {
				return PEAR::raiseError(uber_i18n_en('Error loading database settings'),1);
			}
			
			$usage_data_dsn = $ini['usage']['usage_data_dsn'];
		}
		
		$db = _database_connect($usage_data_dsn);
		if (PEAR::isError($db)) {
			return $db;
		}
		
		return $db;
	}
	
	/**
	 * This method is called immediately before the related service is first
	 * created.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onstart()
	{
		return true;
	}
	
	/**
	 * This method is called immediately before the related service is
	 * edited/updated.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafteredit()
	{
		return true;
	}
	
	/**
	 * This method is called immediately before the related service is
	 * changed to the 'Suspended' state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforesuspend()
	{
		return true;
	}
	
	/**
	 * This method is called immediately after the related service is
	 * changed to the 'Suspended' state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onaftersuspend()
	{
		return true;
	}
	
	/**
	 * This method is called immediately before the related service is
	 * changed from the 'Suspended' state to any other state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforeunsuspend()
	{
		return true;
	}
	
	/**
	 * This method is called immediately after the related service is
	 * changed from the 'Suspended' state to any other state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafterunsuspend()
	{
		return true;
	}
	
	/**
	 * This method is called immediately before the related service is
	 * changed to the 'Canceled' state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforecancel($request = array())
	{
		// make sure we bill for the last period
		$request['now']     = $this->service['end'];
		$request['end']     = $this->service['end'];
		$request['olddate'] = $this->service['end'];
		$request['newdate'] = $this->service['end'];
		
		$this->onbeforerenew($request);
		
		return true;
	}
	
	/**
	 * This method is called immediately after the related service is
	 * changed to the 'Canceled' state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onaftercancel()
	{
		return true;
	}
	
	/**
	 * This method is called immediately before the related service is
	 * changed from the 'Canceled' state to any other state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforeuncancel()
	{
		return true;
	}
	
	/**
	 * This method is called immediately after the related service is
	 * changed from the 'Canceled' state to any other state.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafteruncancel()
	{
		return true;
	}
	
	/**
	 * This method is called immediately before the related service is
	 * renewed.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onbeforerenew($request)
	{
		if (empty($request['now'])) {
			$request['now'] = time();
		}
		
		// Grab service metadata
		$metadata = $this->metadata();
		if (PEAR::isError($metadata)) {
			return $metadata;
		}
		
		// Is circuit ID properly set?
		if (empty($metadata['usage_power_circuit_id'])) {
			return true;
		}
		
		$service = $this->service;
		
		// check we're set to post renew
		if (empty($service['post_renew'])) {
			return PEAR::raiseError(uber_i18n('Usage-Based Power Billing services must be set to post renew.'),1);
		}
		
		// use the current renewal period
		$start_time = $service['renewdate'];
		$end_time   = $request['newdate'];
		
		// don't bill past the end date of the service
		if (!empty($service['end']) && $service['end'] < $end_time) {
			$end_time = $service['end'];
		}
		
		// make sure we don't start earlier than the start date of the service
		$start_time = max($start_time,$service['start']);
		
		// if we've already billed for the period
		if ($end_time <= $start_time) {
			return true;
		}
		
		$request['end']   = $end_time;
		$request['start'] = $start_time;
		
		$report = $this->_report($request,$metadata);
		if (PEAR::isError($report)) {
			return $report;
		}
		
		if (!empty($this->service['discount'])) {
			if ($this->service['discount_type']) {
				$report['amount_due'] -= $this->service['discount'];
			} else {
				$report['amount_due'] *= (1 - ($this->service['discount'] / 100));
			}
		}
		
		// Bill (update service)
		$update = array(
			'price'    => $report['overage_rate'],
			'quantity' => $report['overage'],
			'cost'     => $report['amount_due'],
		);
		$result =  $this->update_service($update);
		if (PEAR::isError($result)) {
			return $result;
		}
		
		// Save details in service notes
		$notes = array();
		if (!empty($report['included'])) {
			$notes[1]  = "\n".''.uber_i18n('Included') .': '. $report['included'] .' '. $report['unit'];
			$notes[1] .= "\n".''.uber_i18n('Overage') .': '. $report['overage'] .' '. $report['unit'];
		} else {
			$notes[1] = "\n".''.uber_i18n('Usage') .': '. $report['overage'] .' '. $report['unit'];
		}
		$result = set_service_notes(array(
			'packid' => $service['packid'],
			'notes'  => $notes,
		));
		if (PEAR::isError($result)) {
			return $result;
		}
		
		return true;
	}
	
	/**
	 * This method is called immediately after the related service is
	 * renewed.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafterrenew()
	{
		return true;
	}
	
	/**
	 * This method is called immediately after payment is received for the
	 * related service.
	 *
	 * @return bool|PEAR_Error
	 */
	public function onafterpayment()
	{
		return true;
	}
	
	/**
	 * This method is called immediately before the related service is
	 * created.
	 *
	 * @return bool
	 */
	public function onbeforecreate()
	{
		return true;
	}
	
	/**
	 * This method is called immediately after the related service is
	 * created.
	 *
	 * @return bool
	 */
	public function onaftercreate()
	{
		return true;
	}
	
	/**
	 * This method is called immediately after an invoice is generated for
	 * the related service
	 *
	 * @return bool
	 */
	public function onafterinvoice()
	{
		return true;
	}
}

// end of script
