<?php
    /**
    * @author Nuno Moreno
    * @copyright 2009-2010 Nuno Moreno
    * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
    * @version 1.0
    * @abstract: Class for checking permissions
    */ 
	// This class was altered by Pedro Pires (The chosen two)
class permClass {
    
    
    //2^0 regular reserve
    //2^1 add ahead
    //2^2 add back  
    //2^3 the code is ready for one more permission....
    
    private $Permission;
    private $warning;
    private $DaysAhead;
    private $MaxSlots;
    private $User;
    private $Resolution;
    private $Slots;
    private $ResourceStatus;
    private $ResourceDelHour;
    private $Resource;
    private $WasAdmin;
    // 14 perm = 14 ->10110 :can delete others, can add ahead

/**
    * @author Nuno Moreno
    * @copyright 2009-2010 Nuno Moreno
    * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
    * @version 1.0
    * @abstract: Sets permission for:
    * @param $user
    * @param $resource
    * @param $passwd
    */

function setPermission($user,$resource,$passwd) {

    $this->User=$user;
    $this->Resource=$resource;
	
	// Encrypts the given password
    // $sql="select password('". $passwd ."')";
    // $res=dbHelp::mysql_query2($sql);
    // $arrcheck=dbHelp::mysql_fetch_row2($res);
	// $arrcheck = cryptPassword($passwd);
    
	// Gets the crypted password from the given user
    $sql="select user_passwd from ".dbHelp::getSchemaName().".user where user_id=". $user;
    $res=dbHelp::mysql_query2($sql);
    $arrpwd=dbHelp::mysql_fetch_row2($res);
	// Gets the password and id of the resource responsible
    $sql="select user_passwd,user_id from ".dbHelp::getSchemaName().".user,resource where user_id=resource_resp and resource_id=" . $resource;
    $res=dbHelp::mysql_query2($sql);
    $arrpwdadmin=dbHelp::mysql_fetch_row2($res);
    
	// Checks if the responsible's password matches the given one
    if ($arrpwdadmin[0]==$passwd){
        $this->WasAdmin=true;
		$sql="select '1111',resource_maxdays,resource_maxslots,resource_status,resource_delhour from resource where resource_id=". $resource;
		$res=dbHelp::mysql_query2($sql) or die ($sql);
		$arr=dbHelp::mysql_fetch_row2($res);
    } else {
        $this->WasAdmin=false;
		$sql="select permissions_level,resource_maxdays,resource_maxslots,resource_status,resource_delhour from permissions,resource where permissions_resource=resource_id and permissions_user=". $this->User ." and permissions_resource=". $resource;
		$res=dbHelp::mysql_query2($sql) or die ($sql);
		$arr=dbHelp::mysql_fetch_row2($res);

		// instead of lpad(bin...)
		$arr[0] = decbin($arr[0]);
		$arr[0] = str_pad($arr[0], 4, "0", STR_PAD_LEFT);
    }
	
	$this->Permission=$arr[0];
    $this->DaysAhead=$arr[1];
    $this->MaxSlots=$arr[2];
    $this->ResourceStatus=$arr[3];
    $this->ResourceDelHour=$arr[4];
    $this->Passwd=$passwd;
    
	// Checks if the user's password matches the converted one given OR if the responsible's password does
    if ($arrpwd[0]==$passwd || $this->WasAdmin) {
        $this->warning='';
        return true;
    } else {
        $this->warning='Wrong password!';
        return false;
    }
}

function getUser($entry){
    $sql="select entry_user from entry where entry_user=" . $entry;
    $res=dbHelp::mysql_query2($sql);
    $arr=dbHelp::mysql_fetch_row2($res);
    $this->User=$arr[0];
}

function getWasAdmin(){
    return $this->WasAdmin;
}

function setSlots($arg) {
    $this->Slots=$arg;
}
function getWarning(){
    return $this->warning;
}

function getResourceStatus(){
    return $this->ResourceStatus;
}

function getResourceDelHour(){
    return $this->ResourceDelHour;
}



//if there is the possibility to add an entry
function addRegular(){
    if (substr($this->Permission,3,1)) {
        return true;
    } else {
        $this->warning='You cannot add entries for this resouce!';
        return false;
    }
        
}

//if there is the possibility to add an entry anytime ahead
function addAhead($date)     {
    if (substr($this->Permission,2,1)) {
        return true;

    } else {
        $year=substr($date,0,4);
        $month=substr($date,4,2);
        $day=substr($date,6,2);
        $hour=substr($date,8,2);
        $min=substr($date,10,2);
        
        
        $Tday=date("d");
        $Tmonth=date("m");
        $Tyear=date("Y");
        $Thour=date("H");
        $Tmin=date("i");
        $times=1;
        if (substr($this->Permission,0,1)) $times=2;// duplicate days ahead for power users/experiments
        if (mktime(8,0,0,$Tmonth,($Tday +$this->DaysAhead*$times),$Tyear)< mktime($hour,$min,0,$month,$day,$year)) {
            $this->warning="You are only allowed to reserve " . $this->DaysAhead*$times . " days ahead";
            return false;
        } else {
            return true;
        }
    }
}

//if there is the possibility to add an entry back in time
function addBack($date)      {
        
    if ( substr($this->Permission,1,1)) {
        return true;
    }
   // echo date("YmdHi");
   // echo $date;
    if (date("YmdHi")>$date) {
        $this->warning='You cannot add entries in the past';
        return false;
    }  else {
        return true;
    }
}
    
//Sets the confirmation IP or manager based depending on the resource configuration
function confirmEntry($entry){
    $this->warning='';
    $cookie='';
    $sql="select user_id,resource_status,resource_confIP,resource_confirmtol,resource_resolution from ".dbHelp::getSchemaName().".user,resource where user_id=resource_resp and resource_id=" . $this->Resource;
    $res=dbHelp::mysql_query2($sql);
    $arrStatus=dbHelp::mysql_fetch_array2($res);
    $sql="select ".dbHelp::getFromDate('entry_datetime','%Y%m%d%H%i')." date, entry_datetime,entry_slots,entry_user  from entry where entry_id=". $entry;
    $res=dbHelp::mysql_query2($sql);
    $arrEntry=dbHelp::mysql_fetch_row2($res);
    
	// confirms that is using an equipment
	// select entry_id, entry_datetime from entry where date_format(entry_datetime, '%Y%m%d')='20110323' and date_format(entry_datetime, '%H%i')>'1500' and entry_resource=5 and entry_status not in (2,3)
    $sql="select entry_id, entry_datetime from entry where ".dbHelp::getFromDate('entry_datetime','%Y%m%d')."='" . substr($arrEntry[0],0,8) ."' and ".dbHelp::getFromDate('entry_datetime','%H%i').">'". substr($arrEntry[0],8,4) . "' and entry_resource=". $this->Resource ." and entry_status not in (2,3)" ;
    $res=dbHelp::mysql_query2($sql) or die ($sql);
    //echo $sql;
    if (dbHelp::mysql_numrows2($res)==0) $this->warning="You might be the last. Confirm with next user!";
    
    switch ($arrStatus['resource_status']) {
    case 4:  // equipment that only manager can confirm entries
        $this->warning='Entry Confirmed!';
        if ($arrStatus['user_id']!=$this->User ){
			
            $this->warning='Only Equipment manager can confirm entry';
            return false;
        }
    break;
    case 3: // equipment that user has to confirm in situ
        //if ($arrStatus['user_id']==$this->User ) break;
        if (isset($_COOKIE["resource_ip"])) $cookie=$_COOKIE["resource_ip"];
        // if response is not the same or
        if (($arrStatus['resource_confIP']!=$_SERVER['REMOTE_ADDR']) && (!strstr($cookie,$arrStatus['resource_confIP'])) && !$this->WasAdmin) {
            $this->warning='Confirmation only possible on equipment computer.' ;
            //$this->warning=trim($arrStatus['resource_confIP']) . '-' . $cookie;
            return false;    
        }
        
        if ($arrEntry[3]!=$this->User and $arrStatus['user_id']!=$this->User) {
            $this->warning='Wrong User' ;
            return false;
        }
        
        $min=substr($arrEntry[0],10,2);
        $hour=substr($arrEntry[0],8,2);
        $year=substr($arrEntry[0],0,4);
        $month=substr($arrEntry[0],4,2);
        $day=substr($arrEntry[0],6,2);
    
        $tol1=$arrStatus['resource_resolution'] * $arrStatus['resource_confirmtol'];
        $tol2=$arrStatus['resource_resolution'] * ($arrStatus['resource_confirmtol'] + $arrEntry[2]);
        $utc1=mktime($hour,$min-$tol1,0,$month,$day,$year);
        $utc2=mktime($hour,$min+$tol2,0,$month,$day,$year);
        
        //echo $utc1;
        //echo $utc2;
        if (!$this->WasAdmin && ((mktime()<$utc1) || (mktime()>$utc2))){
            $this->warning="You can only confirm from " . date("H:i, d M" ,$utc1) . " to " . date("H:i, d M",$utc2);
            return false;
        }
    break;
    case 1: //equipment that does not need confirm. In theory we should not come to this point. Button should be inactive
        //if ($arrStatus[0]==$this->User ) break;
        //if (date("Ymd")!=substr($date,0,8)) {
        //    $this->warning='You can only confirm on entry day!';
        //    return false;
        //}
    break;
    case 0:
        $this->warning='Equipment Inactive!';
        return false;
    break;
    }
    return $arrStatus['resource_status'];
}

//gets entry status to apply depending on equipment status
function getEntryStatus(){
    
    $sql="select resource_status from resource where resource_id=" . $this->Resource;
    $res=dbHelp::mysql_query2($sql);
    $arrStatus=dbHelp::mysql_fetch_row2($res);
    
    switch ($arrStatus[0]) {
    case 0:
        $this->warning="Resource Inactive";
        return false;
    break;
    case 1:
        return 1; // return entry status as regular
    break;
    case 2:
        $this->warning="Resource Invisible";
        return 2; // return entry status as a pre-reserve
    break;
    case 3:
        return 2; // return entry status as a pre-reserve
    break;
    case 4:
        return 2; // return entry status as a pre-reserve
    break;
    }
}

/**
    * @author Nuno Moreno
    * @copyright 2009-2010 Nuno Moreno
    * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
    * @version 1.0
    * @abstract: verifies if there is an overlap. This is more important to eventualy skip an entry in a repeat pattern sequence
    * @param $datetime with format yyyymmddhhii
    */

function checkOverlap($datetime,$slots) {
    //201010100915
    $sql="select resource_resolution from resource where resource_id=" . $this->Resource;
    $res=dbHelp::mysql_query2($sql);
    $arrRes=dbHelp::mysql_fetch_row2($res);
    $this->Resolution=$arrRes[0];
    //echo $datetime;
	//2011.02.25.16.30
    $min=substr($datetime,10,2);
    $hour=substr($datetime,8,2);
    $year=substr($datetime,0,4);
    $month=substr($datetime,4,2);
    $day=substr($datetime,6,2);
    
    //$endtime=date("Y-m-d H:i",mktime($hour,$min+$this->Slots*$this->Resolution,0,$month,$day,$year));
	
    // $sql="select entry_id from entry where entry_datetime< date_add(str_to_date(". $datetime . ",'%Y%m%d%H%i'),interval ". ($this->Resolution* $slots) . " minute) and  str_to_date(". $datetime .",'%Y%m%d%H%i') <date_add(entry_datetime, interval " . $this->Resolution ."*entry_slots minute) and entry_status in (1,2) and entry_resource=". $this->Resource;
    // $sql="select entry_id from entry where entry_datetime < ".dbHelp::date_add(dbHelp::convertDateStringToTimeStamp($datetime,'%Y%m%d%H%i'),$this->Resolution*$slots,'minute')." and ".dbHelp::convertDateStringToTimeStamp($datetime,'%Y%m%d%H%i')." < ".dbHelp::date_add('entry_datetime',$this->Resolution*$slots, 'minute')." and entry_status in (1,2) and entry_resource=". $this->Resource;
    $sql="select entry_id, entry_slots*".$this->Resolution." from entry where entry_datetime < ".dbHelp::date_add(dbHelp::convertDateStringToTimeStamp($datetime,'%Y%m%d%H%i'),$this->Resolution*$slots,'minute')." and entry_status in (1,2) and entry_resource=". $this->Resource;
    $res=dbHelp::mysql_query2($sql);
	$bool = false;
	while($arr = dbHelp::mysql_fetch_row2($res)){
		$sql="select entry_id from entry where entry_id = ".$arr[0]." and ".dbHelp::convertDateStringToTimeStamp($datetime,'%Y%m%d%H%i')." < ".dbHelp::date_add('entry_datetime',$arr[1], 'minute');
		$resAux=dbHelp::mysql_query2($sql);
		if(dbHelp::mysql_numrows2($resAux)>0){
			$bool = true;
			break;
		}
	}

	
   // $sql="select entry_id from entry where str_to_date('$datetime','%Y%m%d%H%i') >= entry_datetime and str_to_date('$datetime','%Y%m%d%H%i') <date_add(entry_datetime, interval ".$this->Resolution."*entry_slots minute) and entry_status in (1,2) and entry_resource=" . $this->Resource;
    //$sql="select entry_id from entry where entry_datetime between str_to_date('" . $datetime . "','%Y%m%d%H%i') and date_add('" . $year . "-" . $month . "-".$day . " ".$hour.":".$min ."', interval " . $this->Resolution."*entry_slots minute) and entry_status in (1,2) and entry_resource=" . $this->Resource;
	
	// $res=dbHelp::mysql_query2($sql);
    // if (dbHelp::mysql_numrows2($res)>0) {

    if ($bool) {
        //$year=substr($endtime,0,4);
        //$month=substr($endtime,4,2);
        //$day=substr($endtime,6,2);
        $this->warning='Entries overlap on ' . $day . ", " . $month . " " . $year;
        return false;
    }
    return true;
}
} // end class

?>