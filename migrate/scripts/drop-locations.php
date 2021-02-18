<?php

$sql = 'DROP TABLE `community-voices_location-category-map`,`community-voices_locations`';
$deleteStatus = $dbHandler->query($sql);

if($deleteStatus){
  echo"Table deleted \n";
}else {
  echo"Table delete Unsuccessful \n";
}

 ?>
