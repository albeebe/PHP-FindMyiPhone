<?PHP

	// This will prevent any errors that occur from potentially displaying your username/password
	error_reporting(0);

	// This should properly display device names that contain special characters
	header("Content-type: text/html; charset=utf-8");

	// Include the FindMyiPhone class
	include ("class.findmyiphone.php");
	
	// This is where we log in to iCloud
	try {
		$fmi = new FindMyiPhone("username_goes_here", "password_goes_here");
	} catch (Exception $e) {
		print "Error: ".$e->getMessage();
		exit;
	}
	
	// This will print out all the devices on your account so you can grab the device IDs
	$fmi->printDevices();
	
	// Find a device that is reporting its location and attempt to get its current location
	foreach ($fmi->devices as $device) {
		if ($device->location->timestamp != "") {
			// Locate the device
			$location = $fmi->locate($device->ID);
			
			print "Device <B>".$device->ID."</B> is located at <I>".$location->latitude.", ".$location->longitude."</I>";
			
			// Play a sound on the device
			$fmi->playSound($device->ID, "You've been located!");
			
			// Lock the device
			//$fmi->lostMode($device->ID, "You got locked out", "555-555-5555");
			
			break;
		}
	}
?>
