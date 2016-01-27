<?php
function fetchdata($configfile, $leases, $networkexclude) {
  $networks=array(); // array for network data
  $ips=array();      // array for the ip data
  $dataset=array();  // array for the return data
  
  $cfc=0;            // counter for the filepointer($cf) array
  $cf[$cfc]=fopen($configfile,"r");
  
  while($cfc>=0){
    while(!feof($cf[$cfc])){
      $buffer=preg_replace("/[[:blank:]]+/"," ",trim(fgets($cf[$cfc]))); // maak van alle tabs/spaties 1 spatie
      $buffert=explode("#",$buffer); // negeer comments
      $buffers=preg_split("/[;{]+/",$buffert[0],-1,PREG_SPLIT_NO_EMPTY); // split naar losse elementen 

      foreach($buffers as $bufferp) {
        $data=explode(" ",trim($bufferp));
        switch($data[0]){
          case "include":
            $cfc++;
            $cf[$cfc]=fopen(trim($data[1],"\""),"r");
            if($cf[$cfc]===false) {
              echo "$data[1] is missing. aborting\n";
              exit(0);
            }
            break;
          case "shared-network":
            if(!strstr(networkexclude,"|".$data[1]."|")){
              $network=$data[1];
            }
            break;
          case "subnet":
            $base=ip2long($data[1]);
            $netmask=0xffffffff^ip2long($data[3]);
            $networks[$network][]=array($base,$netmask);
            $ips[$base]['type']='network';
            $ips[$base+$netmask]['type']='broadcast';
            break;
          case "range":
            $start=ip2long($data[1]);
            if(isset($data[2])) {
              $end=ip2long($data[2]);
              for($i=$start;$i<=$end;$i++){
                $ips[$i]['type']='dhcp';
                $ipstack[$i]="1";
              }
            } else {
              $ips[$start]['type']='dhcp';
              $ipstack[$start]="1";
            }
            break;
          case "option":
            switch($data[1]){
              case "routers":
                $ips[ip2long($data[2])]['type']="gateway";
                break;
            }
            break;
          case "fixed-address":
              $ips[ip2long($data[1])]['type']="fixed-address";
            break;
        }
      }
    }
    fclose($cf[$cfc]);
    $cfc--;
  }

  $filecontent=file($leases);
  foreach($filecontent as $lf){
    $data=explode(" ",trim($lf));
    switch($data[0]){
      case "lease":
        $ip=ip2long($data[1]);
        break;
      case "binding":
        $ips[$ip]['state']=$data[2];
        break;
    }
  }

  foreach($networks as $network=>$pools) {
    $dataset[$network]['size']=0;
    $dataset[$network]['free']=0;
    $dataset[$network]['network']=0;
    $dataset[$network]['broadcast']=0;
    $dataset[$network]['gateway']=0;
    $dataset[$network]['static']=0;
    $dataset[$network]['dhcptotal']=0;
    $dataset[$network]['dhcpabandoned']=0;
    $dataset[$network]['dhcpactive']=0;
    $dataset[$network]['dhcpbackup']=0;
    $dataset[$network]['dhcpexpired']=0;
    $dataset[$network]['dhcpfree']=0;
    $dataset[$network]['dhcpreleased']=0;

    foreach($pools as $pool){
      $dataset[$network]['size']+=$pool[1]+1;
      for($i=$pool[0];$i<=$pool[0]+$pool[1];$i++) {
        if(isset($ips[$i]['type'])) {
          switch($ips[$i]['type']){
            case "network":
              $dataset[$network]['network']++;
              break;
            case "broadcast":
              $dataset[$network]['broadcast']++;
              break;
            case "gateway":
              $dataset[$network]['gateway']++;
              break;
            case "fixed-address":
              $dataset[$network]['static']++;
              break;
            case "dhcp":
              $dataset[$network]['dhcptotal']++;
              if(isset($ips[$i]['state'])) {
                switch($ips[$i]['state']) {
                  case "abandoned;": $dataset[$network]['dhcpabandoned']++; break;
                  case "active;":	  $dataset[$network]['dhcpactive']++; break;
                  case "backup;":    $dataset[$network]['dhcpbackup']++; break;
                  case "expired;":	  $dataset[$network]['dhcpexpired']++; break;
                  case "free;":	  $dataset[$network]['dhcpfree']++; break;
                  case "released;":  $dataset[$network]['dhcpreleased']++; break;
                }
              } else { $dataset[$network]['dhcpfree']++; }
              break;
          }
        } else { 
          $dataset[$network]['free']++; 
        }
      }
    }
  }  
  return($dataset);
}
