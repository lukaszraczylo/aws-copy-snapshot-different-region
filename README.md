aws-copy-snapshot-different-region
==================================



Simple script copying AWS snapshots between regions. 

    root@whatever:/home/nvm# php ec2-snapshot-copy.php vol-abc1234 eu-west-1
    Current availability zone: us-east-1
    Available regions: eu-west-1 sa-east-1 us-east-1 ap-northeast-1 us-west-2 us-west-1 ap-southeast-1 ap-southeast-2
    [i] Using current endpoint ec2.us-east-1.amazonaws.com
    [i] Volume vol-abc1234 found. Looking for snapshots.
    [i] Most recent snapshot found: Thu 21 Feb 2013 - snap-1234
    [i] Copying snapshot snap-1234 from us-east-1 to eu-west-1
    [i] New snapshot id: snap-4321`

* execute script with volume id to copy it's snapshot
* it detects current availability zone using ec2metadata output ( laziness FTW )
* sets endpoint for current zone, checking if volume exists, if yes - checking for snapshots and picking latest one.
* switching ( in background ) endpoint to desired one, copying snapshot
* returning ID of our source snapshot copy, in new region.
