<?PHP

/*
 Copyright (C) Alan Beebe (alan.beebe@gmail.com).
 
 Licensed under the Apache License, Version 2.0 (the "License");
 
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at
 http://www.apache.org/licenses/LICENSE-2.0
 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.

  v1.0 - January 2, 2015
  
*/
 
class FindMyiPhone {

	private $client = array(
						"app-version" => "4.0",
						"user-agent" => "FindMyiPhone/472.1 CFNetwork/711.1.12 Darwin/14.0.0",
						"headers" => array(
							"X-Apple-Realm-Support" => "1.0",
							"X-Apple-Find-API-Ver" => "3.0",
							"X-Apple-AuthScheme" => "UserIdGuest"
						)
					  );
	private $debug;
	private $username;
	private $password;
	private $Apple_MMe_Host;
	private $Apple_MMe_Scope;
	public $devices = array();
	
	/**
     * This is where you initialize FindMyiPhone with your iCloud credentials
     * Example: $fmi = new FindMyiPhone("you@example.com", "MyPassWord123");
     *
     * @param username	iCloud username
     * @param password	iCloud password
     * @param debug		(Optional) Set to TRUE and all the API requests and responses will be printed out
     * @return          FindMyiPhone instance 
     */
	public function __construct($username, $password, $debug = false) {
		$this->username = $username;
		$this->password = $password;
		$this->debug = $debug;
		$this->authenticate();
	}
	
	/**
     * This method attempts to get the most current location of a device
     * Example: $fmi->locate("dCsaBcqBOdnNop4wvy2VfIk8+HlQ/DRuqrmiwpsLdLTuiCORQDJ9eHYVQSUzmWV", 30);
     *
     * @param deviceID	ID of the device you want to locate
     * @param timeout	(Optional) Maximum number of seconds to spend trying to locate the device
     * @return          FindMyiPhoneLocation object 
     */
	public function locate($deviceID, $timeout = 60) {
		$startTime = time();
		$initialTimestamp = $this->devices[$deviceID]->location->timestamp;
		while ($initialTimestamp == $this->devices[$deviceID]->location->timestamp) {
			if ((time() - $startTime) > $timeout) break;
			$this->refreshDevices($deviceID);
			sleep(5);
		}
		return $this->devices[$deviceID]->location;
	}
	
	/**
     * Play a sound and display a message on a device
     * Example: $fmi->playSound("dCsaBcqBOdnNop4wvy2VfIk8+HlQ/DRuqrmiwpsLdLTuiCORQDJ9eHYVQSUzmWV", "Whats up?");
     *
     * @param deviceID	ID of the device you want to play a sound
     * @param message	Message you want displayed on the device
     */
	public function playSound($deviceID, $message) {
		$url = "https://".$this->Apple_MMe_Host."/fmipservice/device/".$this->Apple_MMe_Scope."/playSound";
		$body = json_encode(array("device"=>$deviceID, "subject"=>$message)); 
		list($headers, $body) = $this->curlPOST($url, $body, $this->username.":".$this->password);
	}
	
	/**
     * Put a device into lost mode. The device will immediately lock until the user enters the correct passcode
     * Example: $fmi->lostMode("dCsaBcqBOdnNop4wvy2VfIk8+HlQ/DRuqrmiwpsLdLTuiCORQDJ9eHYVQSUzmWV", "You got locked out", "555-555-5555");
     *
     * @param deviceID		ID of the device you want to lock
     * @param message		Message you want displayed on the device
     * @param phoneNumber	(Optional) Phone number you want displayed on the lock screen
     */
	public function lostMode($deviceID, $message, $phoneNumber = "") {
		$url = "https://".$this->Apple_MMe_Host."/fmipservice/device/".$this->Apple_MMe_Scope."/lostDevice";
		$body = json_encode(array("device"=>$deviceID, "ownerNbr"=>$phoneNumber, "text"=>$message, "lostModeEnabled"=>true)); 
		list($headers, $body) = $this->curlPOST($url, $body, $this->username.":".$this->password);
	}
	
	/**
     * Print all the available information for every device on the users account.
     * This is really useful when you want to get the ID for a device.
     * Example: $fmi->printDevices();
     */
	public function printDevices() {
		if (sizeof($this->devices) == 0) $this->getDevices();
		print <<<TABLEHEADER
        		<PRE>
        		<TABLE BORDER="1" CELLPADDING="3">
        			<TR>
        				<TD VALIGN="top"><B>ID</B></TD>
        				<TD VALIGN="top"><B>name</B></TD>
        				<TD VALIGN="top"><B>displayName</B></TD>
        				<TD VALIGN="top"><B>location</B></TD>
        				<TD VALIGN="top"><B>class</B></TD>
        				<TD VALIGN="top"><B>model</B></TD>
        				<TD VALIGN="top"><B>modelDisplayName</B></TD>
        				<TD VALIGN="top"><B>batteryLevel</B></TD>
        				<TD VALIGN="top"><B>batteryStatus</B></TD>
        			</TR>
TABLEHEADER;
		foreach ($this->devices as $device) {
			$location = <<<LOCATION
			<TABLE BORDER="1">
				<TR>
					<TD VALIGN="top">timestamp</TD>
					<TD VALIGN="top">{$device->location->timestamp}</TD>
				</TR>
				<TR>
					<TD VALIGN="top">horizontalAccuracy</TD>
					<TD VALIGN="top">{$device->location->horizontalAccuracy}</TD>
				</TR>
				<TR>
					<TD VALIGN="top">positionType</TD>
					<TD VALIGN="top">{$device->location->positionType}</TD>
				</TR>
				<TR>
					<TD VALIGN="top">longitude</TD>
					<TD VALIGN="top">{$device->location->longitude}</TD>
				</TR>
				<TR>
					<TD VALIGN="top">latitude</TD>
					<TD VALIGN="top">{$device->location->latitude}</TD>
				</TR>
			</TABLE>
LOCATION;
			print <<<DEVICE
					<TR>
        				<TD VALIGN="top">{$device->ID}</TD>
        				<TD VALIGN="top">{$device->name}</TD>
        				<TD VALIGN="top">{$device->displayName}</TD>
        				<TD VALIGN="top">$location</TD>
        				<TD VALIGN="top">{$device->class}</TD>
        				<TD VALIGN="top">{$device->model}</TD>
        				<TD VALIGN="top">{$device->modelDisplayName}</TD>
        				<TD VALIGN="top">{$device->batteryLevel}</TD>
        				<TD VALIGN="top">{$device->batteryStatus}</TD>
        			</TR>
DEVICE;
		}
		print <<<TABLEFOOTER
        		</TABLE>
        		</PRE>
TABLEFOOTER;
	}
	
	/**
	 *  This is where the users credentials are authenticated.
	 *  The Apple_MMe_Host and Apple_MMe_Scope values are saved and used to generate the URL for all subsequent API calls
	 */
	private function authenticate() {
		$url = "https://fmipmobile.icloud.com/fmipservice/device/".$this->username."/initClient";
		list($headers, $body) = $this->curlPOST($url, "", $this->username.":".$this->password);
		$this->Apple_MMe_Host = $headers["X-Apple-MMe-Host"];
		$this->Apple_MMe_Scope = $headers["X-Apple-MMe-Scope"];
		if ($headers["http_code"] == 401) {
			throw new Exception('Your iCloud username and/or password are invalid');
		}
	}
	
	/**
     * This is where all the devices are downloaded and processed
     * Example: print_r($fmi->devices)
     */
	private function getDevices() {
		$url = "https://".$this->Apple_MMe_Host."/fmipservice/device/".$this->Apple_MMe_Scope."/initClient";
		list($headers, $body) = $this->curlPOST($url, "", $this->username.":".$this->password);
		$this->devices = array();
		for ($x = 0; $x < sizeof($body["content"]); $x++) {
			$device = $this->generateDevice($body["content"][$x]);
			$this->devices[$device->ID] = $device;
		}
	}
	
	/**
	 * This method takes the raw device details from the API and converts it to a FindMyiPhoneDevice object
	 */
	private function generateDevice($deviceDetails) {
		$device = new FindMyiPhoneDevice();	
		$device->API = $deviceDetails;
		$device->ID = $device->API["id"];
		$device->batteryLevel = $device->API["batteryLevel"];
		$device->batteryStatus = $device->API["batteryStatus"];
		$device->class = $device->API["deviceClass"];
		$device->displayName = $device->API["deviceDisplayName"];
		$device->location = new FindMyiPhoneLocation();
		$device->location->timestamp = $device->API["location"]["timeStamp"];
		$device->location->horizontalAccuracy = $device->API["location"]["horizontalAccuracy"];
		$device->location->positionType = $device->API["location"]["positionType"];
		$device->location->longitude = $device->API["location"]["longitude"];
		$device->location->latitude = $device->API["location"]["latitude"];
		$device->model = $device->API["rawDeviceModel"];
		$device->modelDisplayName = $device->API["modelDisplayName"];
		$device->name = $device->API["name"];
		return $device;
	}
	
	/**
	 * This method refreshes the list of devices on the users iCloud account
	 */
	private function refreshDevices($deviceID = "") {
		$url = "https://".$this->Apple_MMe_Host."/fmipservice/device/".$this->Apple_MMe_Scope."/refreshClient";
		if (strlen($deviceID) > 0) {
			$body = json_encode(array("clientContext"=>array("appVersion"=>$this->client["app-version"], "shouldLocate"=>true, "selectedDevice"=>$deviceID, "fmly"=>true)));
		}
		list($headers, $body) = $this->curlPOST($url, $body, $this->username.":".$this->password);
		$this->devices = array();
		for ($x = 0; $x < sizeof($body["content"]); $x++) {
			$device = $this->generateDevice($body["content"][$x]);
			$this->devices[$device->ID] = $device;
		}
	}
	
	/**
	 * Helper method for making POST requests
	 */
	private function curlPOST($url, $body, $authentication = "") {
		$ch = curl_init($url);                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->client["user-agent"]);
		if (strlen($authentication) > 0) {
			curl_setopt($ch, CURLOPT_USERPWD, $authentication);  
		}
		$arrHeaders = array();
		$arrHeaders["Content-Length"] = strlen($request);
		foreach ($this->client["headers"] as $key=>$value) {
			array_push($arrHeaders, $key.": ".$value);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$responseBody = substr($response, $header_size);
		$headers = array();
		foreach (explode("\r\n", substr($response, 0, $header_size)) as $i => $line) {
			if ($i === 0)
            	$headers['http_code'] = $info["http_code"];
			else {
            	list ($key, $value) = explode(': ', $line);
            	if (strlen($key) > 0)
	            	$headers[$key] = $value;
			}
        }
        if ($this->debug) {
        	$debugURL = htmlentities($url);
        	$debugRequestBody = htmlentities(print_r(json_decode($body, true), true));
        	$debugHeaders = htmlentities(print_r($headers, true));
        	$debugResponseBody = htmlentities(print_r(json_decode($responseBody, true), true));
        	print <<<HTML
        		<PRE>
        		<TABLE BORDER="1" CELLPADDING="3">
        			<TR>
        				<TD VALIGN="top"><B>URL</B></TD>
        				<TD VALIGN="top">$debugURL</TD>
        			</TR>
        			<TR>
        				<TD VALIGN="top"><B>Request Body</B></TD>
        				<TD VALIGN="top"><PRE>$debugRequestBody</PRE></TD>
        			</TR>
        			<TR>
        				<TD VALIGN="top"><B>Response Headers</B></TD>
        				<TD VALIGN="top"><PRE>$debugHeaders</PRE></TD>
        			</TR>
        			<TR>
        				<TD VALIGN="top"><B>Response Body</B></TD>
        				<TD VALIGN="top"><PRE>$debugResponseBody</PRE></TD>
        			</TR>
        		</TABLE>
        		</PRE>
HTML;
        }
		return array($headers, json_decode($responseBody, true));
	}
}


class FindMyiPhoneDevice {
	public $ID;
	public $batteryLevel;
	public $batteryStatus;
	public $class;
	public $displayName;
	public $location;
	public $model;
	public $modelDisplayName;
	public $name;
	public $API;
}


class FindMyiPhoneLocation {
	public $timestamp;
	public $horizontalAccuracy;
	public $positionType;
	public $longitude;
	public $latitude;
}
