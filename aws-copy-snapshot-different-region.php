<?php
/*
	Lukasz Raczylo, lukasz[a]raczylo.com
	License: Beerware;
*/
date_default_timezone_set('Europe/London');
if($argc != 3) {
	echo "Usage: php ". $argv[0] ." volume_id dest_region\n";
	exit(0);
}
require('/usr/share/php/AWSSDKforPHP/sdk.class.php');
$ec2 = new AmazonEC2();
$response_available_regions = $ec2->describe_regions();
$available_regions = array();
$new_endpoint = "";
$new_region = "";
exec('ec2metadata | grep availability-zone | awk \'{print $2}\'', $ec2metadata_tmp);
$ec2metadata = $ec2metadata_tmp[0];
if ($ec2metadata == "" || $ec2metadata == '1') {
	$ec2metadata = 'eu-west-1';
} else {
	$ec2metadata = substr_replace($ec2metadata, "", -1);
}
echo "Current availability zone: ".$ec2metadata."\n";
echo "Available regions: ";
foreach($response_available_regions->body->regionInfo->item as $region) {
	echo $region->regionName ." ";
	$available_regions[] = array( "name" => $region->regionName, "endpoint" => $region->regionEndpoint[0] );
}
echo "\n\n";
foreach($available_regions as $single_region) {
	if ($single_region["name"] == $argv[2]) {
		$new_endpoint = $single_region["endpoint"];
		$new_region = $single_region["name"];
	}
	elseif ($single_region["name"] == $ec2metadata) {
		$old_endpoint = $single_region["endpoint"];
		$old_region = $single_region["name"];
	}
}
echo "[i] Using current endpoint ". $old_endpoint ."\n";
$ec2->set_hostname($old_endpoint);
$volume_information = $ec2->describe_volumes(array("VolumeId"=>$argv[1]));
if($volume_information->status == 200) {
	echo "[i] Volume ". $argv[1] ." found. Looking for snapshots.\n";
	$snapshot_response = $ec2->describe_snapshots(array("Filter"=>array(array("Name" => 'volume-id', "Value" => $argv[1]), array("Name" => 'progress', "Value" => '100%'))));
	$snapshots = array();
	foreach ($snapshot_response->body->snapshotSet->item as $snapshot) {
		$snapshots[] = $snapshot;
	}
	if(count($snapshots) == 0) {
		echo "[i] No snapshots found for volume ". $argv[1] ." :(\n";
	} else {
		$most_recent = array_pop($snapshots);
		echo "[i] Most recent snapshot found: " .date('D d M Y', strtotime($most_recent->startTime)) ." - ". $most_recent->snapshotId ."\n";
		echo "[i] Copying snapshot ". $most_recent->snapshotId ." from ". $old_region. " to ". $new_region ."\n";
      $ec2->set_hostname($new_endpoint);
		$new_snapshot = $ec2->copy_snapshot($old_region, $most_recent->snapshotId, array("Description" => 'Copy of '.$most_recent->snapshotId.' from '.$old_region));
		if ($new_snapshot->body->snapshotId == "") {
			exit("[!] No information about new snapshot - probably snapshot creation limit reached\n");
		} else {
			echo "[i] New snapshot id: ". $new_snapshot->body->snapshotId ."\n";
		}
	} 
} else {
	echo "[i] Unable to find volume ". $argv[1] ." in current availability zone\n";
}
?>
