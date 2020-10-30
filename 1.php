<?php

function humanFileSize($size, $unit="") {
  if( (!$unit && $size >= 1<<30) || $unit == "GB")
    return number_format($size/(1<<30),2)." GB";
  if( (!$unit && $size >= 1<<20) || $unit == "MB")
    return number_format($size/(1<<20),2)." MB";
  if( (!$unit && $size >= 1<<10) || $unit == "KB")
    return number_format($size/(1<<10),2)." KB";
  return number_format($size)." bytes";
}

function post($action) {
	// Prepare the POST data


//  VirMach KVM 启用API授权
// API Key: 54<<你懂得>>TS
// API Hash: 8d<<你懂得>>d8a

	// <configure> :
  //这里填写api的key
	$postfields["key"] = "54<<你懂得>>TS";
	//这里填写api的hash
	$postfields["hash"] = "8d<<你懂得>>d8a";

	$masterurl = "https://solusvm.virmach.com/";
	
	// </configure>
	
	$postfields["action"] = $action;
	$postfields["status"] = "true";
	
	if($action == "info") {
		$postfields["hdd"] = "true";
		$postfields["mem"] = "true";
		$postfields["bw"] = "true";
	}

	// Prepare the POST request

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $masterurl."api/client/command.php");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:  "));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	// Execute the request

	$data = curl_exec($ch);
	
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	if($code != 200) {
		$return['error'] = 1;
		
		if($code == 405) {
			$return['message'] = "Incorrect API credentials.";
			return $return;
		}
		
		$return['message'] = "Invalid status code.";
		
		return $return;
	}

	// Close the Curl handle

	curl_close($ch);
	
	if(!$data) {
		$return['error'] = 1;
		$return['message'] = "Error connecting to API.";
		
		return $return;
	}

	// Extract the data

	preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $match);

	$result = array();

	foreach ($match[1] as $x => $y) {
		$result[$y] = $match[2][$x];
	}
	
	if($result['status'] == "error") {
		$return['error'] = 1;
		$return['message'] = $result['statusmsg'];
		
		return $return;
	}

	$return = $result;
	$return['error'] = 0;
	
	return $return;
}

if(isset($_GET['action'])) {
	$action = $_GET['action'];
} else {
	$action = "info";
}

switch($action)
{
	case 'info':
		$result = post("info");
		
		if($result['error'] == 0) {
			$return = $result;
			
			$return['hdd'] = explode(",", $return['hdd']);
			$return['mem'] = explode(",", $return['mem']);
			$return['bw'] = explode(",", $return['bw']);
		} else {
			$return = $result;
		}
		
		break;
	
	default:
		$return['error'] = 1;
		$return['message'] = "Invalid action specified.";
}

?>

 <!DOCTYPE html>
<html id="spLianghui">
<head>
	<title>服务器状态</title>
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<div style="width: 90%; max-width: 800px; margin: 0 auto;">
		<h1>服务器状态</h1>
		<hr/>
		
		<?php if($return['error'] == 1) { ?>
			<div class="alert alert-danger" role="alert"><?php echo $return['message']; ?></div>
			
			<a class="btn btn-default btn-block" href="?action=info" role="button">返回到监控页面</a>
		<?php } else { ?>
		
		<?php if($action == "info") { ?>
				
			<h3>主机名</h3>
			<?php echo $return['hostname']; ?>
			
			<h3>状态</h3>
			<?php echo $return['vmstat']; ?>
			
			<h3>带宽 <small>  已用 <?php echo humanFileSize($return['bw'][1]); ?> ， 总量 <?php echo humanFileSize($return['bw'][0]); ?></small></h3>
			<div class="progress">
				<div class="progress-bar" role="progressbar" aria-valuenow="<?php echo $return['bw'][3]; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $return['bw'][3]; ?>%;">
					<?php echo $return['bw'][3]; ?>%
				</div>
			</div>
			
		<?php } ?>		
		
		<?php } ?>
	</div>
</body>
</html>

