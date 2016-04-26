<?php
/**
 * Amazon Route 53 Module
 *
 * Module to auto-setup Amazon Route 53 module integration
 * 
 * 
 * When complete, place your finished device module into
 * include/service_modules/
 * 
 * @author Samer Bechara <sam@thoughtengineer.com>
 * @url http://thoughtengineer.com/
 * @version 1.0
 * @package ubersmith
 * @subpackage default
 **/

 /*
  * Define module constants below
  */
  
  /* Uncomment the path to the r53 module class, download from http://sourceforge.net/projects/php-r53/ */
  // define("ROUTE53_PATH", '/path/to/r53.php');
  
  /* Uncomment the path to your Ubersmith API client found at 
  // https://github.com/TeamUbersmith/api_clients/blob/master/php/class.uber_api_client.php. */
  //define("UBER_API_PATH",'/path/to/class.uber_api_client.php');
  
  // The URL to your ubersmith instance
  // define("UBER_API_URL",'http://billing.example.com/');
  
  // This Ubersmith user should be given API access and access to all brands. Should be created in Ubersmith admin area
  
  // define("UBER_API_USER",'username');
  // define("UBER_API_TOKEN",'access_token');
  
 /* Include R53 External Library */
  require_once(ROUTE53_PATH);
  require_once(UBER_API_PATH);
  
/**
 * Amazon Route 53 Class
 *
 * @package ubersmith
 * @author Samer Bechara <sam@thoughtengineer.com>
 */
class sm_amazon_dns extends service_module
{
	// AWS Route 53 object
	protected $aws;

	/**
	 * The name of the service module. This title will be shown in the 'Service Plans'
	 * section of 'setup & admin', as well as within the client services in the
	 * Client Manager.
	 *
	 * @return string
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	public static function title()
	{
		return 'Amazon Route 53';
	}

	/**
	 * The 'get_metadata_config()' will automatically create custom data fields if they do
	 * not exist when the service module is installed. 'type' can be 'text' or 'select'.
	 * If using 'select', pass an 'options' key with an array of your values to be presented
	 * in the select box. See below for a (commented out) example.
	 * 
	 * @return array
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */

function get_metadata_config()
        {
                return array(


                        array(
                                'group_name' => 'Amazon Web Services',
                                'items'      => array(
                                        'amazon_zone_id'         => array(
                                                'meta_type' => 'service',
                                                'prefix'      => 'Amazon Route 53 Hosted Zone ID (For internal Use Only)',
                                                'type'        => 'text',
                                                'size'        => 50,
                                                'default_val' => '',
                                        ),
                                )
                        )
                );
        }
		
	/**
	 * The 'summary' function allows you to display some useful information related to
	 * your service module on a per client service basis. This will be displayed in the 
	 * lower right hand corner of the service's page when viewed in the Client Manager.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function summary()
	{
                $GLOBALS['controls'][] = '<div style="margin: 4px;">'. gui::popup_link($this->view_url(),uber_i18n('Edit DNS Records'),'_blank',array('height'=>'720','width'=>'1024')) .'</div>';
		
                $this->listRecords();

		return true;
	}
	
	// Displays the DNS records manager for a specific service plan
	function view(){
	
                echo '<a href="'; echo $this->view_url(); echo '">View Records</a> - <a href="'.$this->view_url(array('r53_action' => 'add_form')).'#">Add Record</a><br/><br/>';
		$records = $this->getRecords();
                
                if( $_GET['r53_action'] == 'delete'){
                    
                    $record['record_name'] = $_GET['record_name'];
                    $record['record_type'] = $_GET['record_type'];
                    $record['record_ttl'] = $_GET['record_ttl'];
                    $record['record_value'] = $_GET['record_value'];
                    
                    $this->deleteRecord($record);
                    
                    echo "Record is being deleted, please allow a few seconds for your request to be completed.";
                    
                    return;
                } 
                
                // Show add record form
                elseif ($_GET['r53_action']=='add_form'){
                    echo $this->addForm();
                    return;
                }
                elseif ($_GET['r53_action'] == 'add_record') {
                    
                    
                    $record['name'] = $_POST['record_name'];
                    $record['record_type'] = $_POST['record_type'];
                    $record['ttl'] = $_POST['record_ttl'];
                    $record['value'] = $_POST['record_value'];
                    
                    $result = $this->addRecord($record);
                    
                    //var_dump($result);
                    if(isset($result['Error'])){
                        echo "An error has occurred, here is the message provided by our DNS server.<br/><br/>";
                        echo "Technical details<br/><br/>
                            Message: ".$result['Error']['Message']."<br/><br/>
                            Code: ".$result['Error']['Code']."<br/><br/>
                            Request ID: ".$result['RequestId']."<br/><br/>";
                    }
                    
                    else 
                        echo "Record has been added, please allow a few seconds for changes to take effect.";
                    
                    return;
                }               
                
                $this->listRecords(false);
		//return true;
	}

		// List all records for a specific Zone
        function listRecords($readonly = true){
            
                $records = $this->getRecords();
		echo "	<table style='border: 1px solid black; width:100%;'>
                            <thead>
                                <tr>
                                    <th style='text-align:left;'>Name</th>
                                    <th style='text-align:left;'>Type</th>
                                    <th style='text-align:left;'>Value</th>
                                    <th style='text-align:left;'>TTL</th>";
                                    
                                // Show column only if table is not read only
                                if(!$readonly)
                                        echo "<th style='text-align:left;'>Actions</th>
                                </tr>
                            </thead>
                            <tbody>";

                        foreach($records as $key => $rec){
                                echo "		
                                <tr>
                                        <td style='text-align:left;border: 1px solid black;'>".$rec['Name']."</td>
                                        <td style='text-align:left;border: 1px solid black;'>".$rec['Type']."</td>
                                        <td style='text-align:left;border: 1px solid black;'>"; 

                                        foreach ($rec['ResourceRecords'] as $key2 => $value){

                                            echo $value."<br/>";
                                        }

                                        echo"</td>
                                        <td style='text-align:left;border: 1px solid black;'>".$rec['TTL']."</td>";
                                        
                            //Show Delete button if table is not readonly                                        
                            if(!$readonly){
                                            
                                        
                                        echo "<td style='text-align:left;border: 1px solid black;'>"; 

                                // MX records contain more than one array key, concatenate in URL
                                if($rec['Type'] == 'MX'){
                                    $rec_val = urlencode(implode("\r\n", $rec['ResourceRecords']));                                    
                                }
                                else
                                    $rec_val = $rec['ResourceRecords'][0];
                                    
                                // HIDE delete button for NS and SOA records
                                if($rec['Type']!== 'NS' && $rec['Type']!='SOA'){
                                                                                 echo "<a href='".$this->view_url(array('record_name'=> urlencode($rec['Name']), 'record_type' => $rec['Type'], 'record_ttl' => $rec['TTL'], 'record_value' => $rec_val, 'r53_action' => 'delete'))."#'>Delete</a>";
                                }
                                echo "</td>";
                            } // End of delete button
                                
                                echo "</tr>";
                        }

            echo "         </tbody>
                        </table>
				";            
        }
	/**
	 * This function returns an array of configuration options that will be 
	 * displayed when the module is configured for a service plan. You can
	 * add as many configuration items as you like. Retrieval of the configuration
	 * data is shown in the summary() function above.
	 *
	 * @return array
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function config_items()
	{
		return array(
			'aws_access_key' => array(
				'label'  => uber_i18n('AWS Access Key'),
				'type'   => 'text',
				'size'   => '50',
			),
			'aws_secret_key' => array(
				'label'  => uber_i18n('AWS Secret Key'),
				'type'   => 'text',
				'size'   => '50',
			),						
		);
	}


	/**
	 * These supporting functions can return 'true' when successful, or a PEAR error
	 * object on failure. As an example:
	 * 
	 * return PEAR::raiseError('Something terrible happened, send for help',1);
	 */

	/**
	 * The install function directs the service module to perform tasks upon creation.
	 * This might include creating metadata items, reaching out to a remote service, or
	 * any number of tasks that you would like to perform upon initial setup.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function install()
	{
		$result = $this->install_metadata();
			
		if (PEAR::isError($result)) {
			return $result;
		}
				
		return true;
	}

	/**
	 * This function is called immediately before the related service is first created.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 **/
	function onstart()
	{
		
		return $this->createZone();
	}

	// Creates a hosted zone on Amazon Route 53, called by other Ubersmith functions
	function createZone(){            
	
		// Get service info
		$service = $this->service;
		
		// Create new route 53 object, with credentials
		$r53 = new Route53($this->config('aws_access_key'), $this->config('aws_secret_key'));
				
		// Create Hosted DNS Zone with the same domain name
		$result = $r53->createHostedZone($service['servername'], $service['packid'].time(), 'Created by Amazon Route 53 Ubersmith Module');
		
		// Connect to API in order to update zone ID field
		$client = new uber_api_client(UBER_API_URL, UBER_API_USER,UBER_API_TOKEN);
		
		try {
			$result = $client->call('client.service_update',array(
				'service_id' => $service['packid'],
				'meta_amazon_zone_id' => $result['HostedZone']['Id']
			));
			
			
		} catch (Exception $e) {
			print 'Error: '. $e->getMessage() .' ('. $e->getCode() .')';die();
		}
	
		return true;	
	}
	
	// Returns the hosted Zone ID in route 53
	function getZoneId() {	

		// Get service object
		$service = $this->service;
		
		// Connect to API in order to get zone ID value
		$client = new uber_api_client(UBER_API_URL, UBER_API_USER,UBER_API_TOKEN);
		
		try {
			$hosted_zone_id = $client->call('client.service_metadata_single',array(
				'service_id' => $service['packid'],
				'variable' => 'amazon_zone_id'
			));
						
		} catch (Exception $e) {
			print 'Error: '. $e->getMessage() .' ('. $e->getCode() .')';die();
		}	

		return $hosted_zone_id;
	}
	
	// Deletes the zone associated with the current service, called when service is cancelled
	function deleteZone(){
		
		$hosted_zone_id = $this->getZoneId();
		
		$r53 = new Route53($this->config('aws_access_key'), $this->config('aws_secret_key'));
                
                // Delete all records which have been added to be able to delete zone
                $records = $r53->listResourceRecordSets($hosted_zone_id);
                
                // Prepare deletion commands
                $changes = array();
                
                foreach($records['ResourceRecordSets'] as $key => $record){
                    
                    // Skip NS and SOA records
                    if( ($record['Type'] == 'NS') || ($record['Type'] == 'SOA') )
                        continue;
                    
                    // MX records contain more than one array key, concatenate in URL
                    if($record['Type'] == 'MX'){
                        $rec_val = $record['ResourceRecords'];                                    
                    }
                    else
                        $rec_val = $record['ResourceRecords'][0];
                    
                    // Add record to be deleted to array
                    $changes[] = $r53->prepareChange('DELETE', $record['Name'], $record['Type'], $record['TTL'], $rec_val);
                }
                            
		// Process deletion                
                $result = $r53->changeResourceRecordSets($hosted_zone_id, $changes);                
                
		// Delete Hosted DNS Zone with the same domain name
		$result = $r53->deleteHostedZone($hosted_zone_id);
			
                //var_dump($result);die();
		return true;		
	
	}
        
        // Displays the add record form
        function addForm(){
            
            $form = '<form action="'.$this->view_url(array('r53_action' =>'add_record')).'" method="post">
                        Name: <input type="text" name="record_name" /> ( Enter full value such as test.example.com )<br/>
                        Type: &nbsp;<select name="record_type">
                                <option value="A">A - IPv4 Address</option>
                                <option value="CNAME">CNAME - Canonical Name</option>
                                <option value="MX">MX - Mail Exchange</option>
                              </select><br/>
                        Record Value: <br/><textarea rows="4" cols="50" name="record_value"></textarea><br/>
                        TTL in seconds: <input type="text" name="record_ttl" value="86400" /><br/>
                        <input type="submit" value="Add Record" />
                    </form>';
            
            $form.= '<h3>Instructions for filling value fields</h3>
                    <p><strong>A records:</strong> Enter an IP address, e.g. 192.168.0.1<br/><br/>
                       <strong>CNAME Records:</strong> Enter a full domain name, e.g. test.example.com<br/><br/>
                       <strong>MX records:</strong> enter each MX value on one line, along with its priority, e.g:<br/><br/>
                       10 mail.example.com<br/>
                       20 mail2.example.com<br/>
                       ';
            
            
            return $form;
        }
   /*
    * Method to add a record to our DNS zone
	* 
	* @param string name The name to perform the action on.
	*                    If it does not end with '.', then AWS treats the name as relative to the zone root.
	* @param string type The type of record being modified.
	*                    Must be one of: A, AAAA, CNAME, MX, NS, PTR, SOA, SPF, SRV, TXT
	* @param int ttl The time-to-live value for this record, in seconds.
	* @param array records An array of resource records to attach to this change.
	*                      Each member of this array can either be a string, or an array of strings.
	*                      Passing an array of strings will attach multiple values to a single resource record.
	*                      If a single string is passed as $records instead of an array,
	*                      it will be treated as a single-member array.
	*/

	function addRecord($record_fields){
	
                // Get record fields
		$name = $record_fields['name'];
                $record_type = $record_fields['record_type'];
                $ttl = $record_fields['ttl'];
                $value = $record_fields['value'];

                // For MX fields, explode value into array
                if($record_type == 'MX')
                    $value = explode("\r\n", $value);
                
		// Create new Amazon DNS object
		$r53 = new Route53($this->config('aws_access_key'), $this->config('aws_secret_key'));
		
		$zone_id = $this->getZoneId();
		$change = $r53->prepareChange('CREATE', $name, $record_type, $ttl, $value);               

                return $r53->changeResourceRecordSets($zone_id, $change);

	}
        
        /* Deletes a record
        * @param array $record  An array of containing the following fields
        * $record['record_name'], $record['record_type'], $record['record_ttl'], $record['record_value']
	*/
        private function deleteRecord($record){            
                            
                // For MX fields, explode value into array
                if($record['record_type'] == 'MX')
                    $record['record_value'] = explode("\r\n", urldecode($record['record_value']));
                
                //var_dump($record);die();
                                
		// Create new Amazon DNS object
		$r53 = new Route53($this->config('aws_access_key'), $this->config('aws_secret_key'));
		
		$zone_id = $this->getZoneId();
                $change = $r53->prepareChange('DELETE', $record['record_name'], $record['record_type'], $record['record_ttl'], $record['record_value']);
                $result = $r53->changeResourceRecordSets($zone_id, $change);
                
                return $result;
        }
	
	/**
	 * This function is called immediately before the related service is edited/updated.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 **/
	 
	// Get list of records in zone
	function getRecords(){
	
		// Create new Amazon DNS object
		$r53 = new Route53($this->config('aws_access_key'), $this->config('aws_secret_key'));
		
		// Get zone ID
		$zone_id = $this->getZoneId();
		
		// Get list of records
		$result  = $r53->listResourceRecordSets($zone_id);
		return $result['ResourceRecordSets'];
	
	
	}
	function onafteredit()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is changed to
	 * the 'Suspended' state.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onbeforesuspend()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is changed to
	 * the 'Suspended' state.
	 * 
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onaftersuspend()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is changed 
	 * from the 'Suspended' state to any other state.
	 * 
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onbeforeunsuspend()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is changed 
	 * from the 'Suspended' state to any other state.
	 * 
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onafterunsuspend()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is changed 
	 * to the 'Cancelled' state.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onbeforecancel()
	{
		$this->deleteZone();
		return true;
	}

	/**
	 * This function is called immediately after the related service is changed 
	 * to the 'Cancelled' state.
	 * 
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onaftercancel()
	{
		return true;
	}

	/**
	 * This function is called immediately before the related service is changed 
	 * from the 'Cancelled' state to any other state.
	 * 
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onbeforeuncancel()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is changed 
	 * from the 'Cancelled' state to any other state.
	 * 
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onafteruncancel()
	{
		return $this->createZone();
	}

	/**
	 * This function is called immediately before the related service is renewed.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onbeforerenew()
	{
		return true;
	}

	/**
	 * This function is called immediately after the related service is renewed.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onafterrenew()
	{
		return true;
	}

	/**
	 * This function is called immediately after payment is received for the related service.
	 *
	 * @return bool
	 * @author Samer Bechara <sam@thoughtengineer.com>
	 */
	function onafterpayment()
	{
		return true;
	}

}
