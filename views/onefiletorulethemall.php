<?php
	// security needs fixing, check ZM_AUTH_TYPE and that the user is logged in, etc

	/* begin functions */
	function updateUserDefaultPreset($userId, $defaultPresetId) {
		$query = "UPDATE Users SET defaultPreset='{$defaultPresetId}' WHERE Id='{$userId}'";
		$result = dbQuery($query);
		if(!$result) {
			return false;
		}
		else {
			return true;
		}
	}

	function userTableInsertPresetColumn() {
		$query = "ALTER TABLE Users ADD defaultPreset INT(10) NOT NULL default '-1'";
		$result = dbQuery($query);
		if(!$result) {
			return false;
		} 
		else {
			return true;
		}
	}

	function userTablePresetCheck() {
		$query = "SELECT * FROM Users WHERE Id='" . $_SESSION['user']['Id'] . "'";
		$result = dbFetchOne($query);
		return array_key_exists('defaultPreset', $result);
	}
	/* end functions */

	if(isset($_REQUEST['getUserDefaultPresetId'])) {
		if(isset($_SESSION['user']['Username'])) {
			echo getUserDefaultPresetId($_SESSION['user']['Id']);
		}
	}

	if(isset($_REQUEST['updateUserDefaultPreset'])) {
		// check if the Users table has 'defaultPreset'
		if(userTablePresetCheck()===false) {
			if(userTableInsertPresetColumn()===false) {
				die("error 2");
			}
		}

		if((ctype_digit($_REQUEST['defaultPresetId']) === false)&&($_REQUEST['defaultPresetId']!=="-1")) {
			die("error 3");
		}

		if(updateUserDefaultPreset($_SESSION['user']['Id'], $_REQUEST['defaultPresetId']) === true) {
			echo "success";
		}
		else {
			die("error 4");
		}
	}

	if(isset($_REQUEST['q'])) {
		$query = "SELECT Id, MonitorId, StartTime, Frames FROM Events WHERE ";
		$datetimes = explode(",", $_REQUEST['q']);
		$i=0;
		foreach($datetimes as $datetime) {
			if($i !== 0) {
				$query .= " OR ";
			}
			$query .= "StartTime='{$datetime}'";
			$i++;
		}

		$results = dbFetchAll($query);

    	$scale = max( reScale( SCALE_BASE, '100', ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );

		foreach ($results as $result) {
			for($counter = 1; $counter <= $result['Frames']; $counter++) {
		        $event['Id']=$result['Id'];
		        $event['StartTime']=$result['StartTime'];
		        $event['MonitorId']=$result['MonitorId'];
				$imageData = getImageSrc($event, $counter, $scale, (isset($_REQUEST['show']) && $_REQUEST['show']=="capt"));
		        $imagePath = $imageData['thumbPath'];
		        $eventPath = $imageData['eventPath'];
		        $dImagePath = sprintf("%s/%0".ZM_EVENT_IMAGE_DIGITS."d-diag-d.jpg", $eventPath, $counter);
		        $rImagePath = sprintf("%s/%0".ZM_EVENT_IMAGE_DIGITS."d-diag-r.jpg", $eventPath, $counter);
		        $frames[$result['MonitorId']][$result['Id']][] = "/zm/" . viewImagePath($imagePath);
			}
		}
    	echo json_encode($frames);
	}

	if (isset($_REQUEST['timeline'])) {
		$cameras = "'" . implode("','", explode(",", $_REQUEST['cameras'])) . "'";
		//echo json_encode(dbFetchAll("SELECT Events.Id, Events.MonitorId, Events.StartTime, Events.EndTime, Events.Frames, Events.MaxScore FROM Events, Monitors WHERE Events.MonitorId=Monitors.Id AND Events.MonitorId IN (" . $cameras . ") AND StartTime BETWEEN '" . $_REQUEST['start'] . "' AND '" . $_REQUEST['end'] . "'"));
		echo json_encode(dbFetchAll("SELECT Events.Id, Events.MonitorId, Monitors.Name, Events.StartTime, Events.EndTime AS Date, Events.StartTime, Events.EndTime, Events.Frames, Events.MaxScore FROM Events, Monitors WHERE Events.MonitorId=Monitors.Id AND Events.MonitorId IN (" . $cameras . ") AND StartTime BETWEEN '" . $_REQUEST['start'] . "' AND '" . $_REQUEST['end'] . "'"));
	}
?>