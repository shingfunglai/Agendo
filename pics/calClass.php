<?php
include_once('collectionClass.php');
/**
  * @author Nuno Moreno
  * @copyright 2009-2010 Nuno Moreno
  * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
  * @version 1.0
  * @abstract Class for configure a single cell in the calendar. This cells will be part of the calendar object, which
  * contains a collection of cell classes.
  * Because I was not able to recall this subclass in the main code it might not be worthwhile doing it
*/

class calCell {
    private $User;
    private $Entry;
    private $NextEntry;
    private $NSlots;
    private $StartTime;
    private $Tag;
    private $StartDate;
    private $Repeat;
    private $EntryStatus;
    private $NextUser;
    
    function setUser($arg)      {$this->User=$arg;}
    function setEntry($arg)     {$this->Entry=$arg;}
    function setNextEntry($arg) {$this->NextEntry=$arg;}
    function setNSlots($arg)    {$this->NSlots=$arg;}
    function setStartTime($arg) {$this->StartTime=$arg;}
    function setTag($arg)       {$this->Tag=$arg;}
    function setStartDate($arg) {$this->StartDate=$arg;}
    function setRepeat($arg)    {$this->Repeat=$arg;}
    function setEntryStatus($arg)    {
        if ($arg==2 or $arg==4) {
                $datetime=$this->getStartDate(). $this->getStartTime();
                $min=substr($datetime,10,2);
                $hour=substr($datetime,8,2);
                $year=substr($datetime,0,4);
                $month=substr($datetime,4,2);
                $day=substr($datetime,6,2);
                $endtime=mktime($hour,$min + (cal::getConfTolerance()+$this->getNSlots()) * cal::getResolution()*60 ,0,$month,$day,$year);
                $now=mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"));
                if ($endtime<$now) {
                    $arg=9;
                }
        }
        
        $this->EntryStatus=$arg;
    }
    function setNextUser($arg)    {$this->NextUser=$arg;}
    
    function getUser ()         {return $this->User;}
    function getEntry ()        {return $this->Entry;}
    function getNextEntry ()    {return $this->NextEntry;}
    function getNSlots ()       {return $this->NSlots;}
    function getStartTime ()    {return $this->StartTime;}
    function getTag ()          {return $this->Tag;}
    function getStartDate ()    {return $this->StartDate;} // first day of the week
    function getRepeat ()       {return $this->Repeat;}
    function getEntryStatus ()  {return $this->EntryStatus;}
    function getNextUser()       {return $this->NextUser;}
    /**
     * 
     * @method: tagging a calenadar cell with slottype in arguments : 0 without entry, 1 with regular entry, 2 pre-reserve,3 deleted,4 monitored, 9 (not defined) for error status (eg did not confirm)
     * @param: resource
     */ 
    function designSlot ($slotType) {
        // case there is an entry
        if ($this->EntryStatus==1) $cellbgStrong=cal::RegCellColorOn;
        if ($this->EntryStatus==2) $cellbgStrong=cal::PreCellColorOn;
        if ($this->EntryStatus==4) $cellbgStrong=cal::MonCellColorOn;
        if ($this->EntryStatus==9) $cellbgStrong=cal::ErrCellColorOn;
        
        if ($this->EntryStatus==1) $cellbgLight=cal::RegCellColorOff;
        if ($this->EntryStatus==2) $cellbgLight=cal::PreCellColorOff;
        if ($this->EntryStatus==4) $cellbgLight=cal::MonCellColorOff;
        if ($this->EntryStatus==9) $cellbgLight=cal::ErrCellColorOff;
        
        switch ($slotType){
        case 0: // without entry
            $extra="OnMouseOver='swapColor(this.style,0,0);' OnMouseDown='swapColor(this.style,1,0);'";
            break;
        case 1: // with entry
            $extra="OnMouseDown='swapColor(this.style,1,1);'";
            if ($this->getRepeat()!='') {
                $extra = $extra . " style='background:$cellbgStrong'";
            } else {
                $extra = $extra . " style='background:$cellbgLight'";
            }
            break;
        }

        echo "<td align=center lang=" . $this->Entry ." width=10% " . $extra ." rowspan=". $this->NSlots .">". $this->Tag ."</td>\n";        
        
    }    
}

/**
  * @author Nuno Moreno
  * @copyright 2009-2010 Nuno Moreno
  * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
  * @version 1.0
  * @abstract: the engine for building the calendar. It does it all!
  *
*/

class cal extends phpCollection{
    
    private $Duration=1;
    private static $Resolution;
    private $StartTime;
    private $EndTime;
    
    private $Day;
    private $Resource;
    private $StartDate;
    private $SlotStart;
    private $Slot=array();
    private $MaxSlots;
    private $CalRepeat;
    private static $ConfTolerance;
    private $ResourceName;
    private $Status;
    private $StatusName;
    private $Link;
    private $RespName;
    private $RespEmail;
    private $DelTolerance;
    
    const RegCellColorOn= '#e3f8a1';
    const RegCellColorOff= '#e4efc2';
    
    const PreCellColorOn= '#f9f4a6';
    const PreCellColorOff= '#f8f6cf';

    const MonCellColorOn= '#afdde5';
    const MonCellColorOff= '#d7e9ec';

    const ErrCellColorOn='#f39ea8';
    const ErrCellColorOff='#f8dada';
    
    
    //private $ResStatus;
    function __construct ($Resource){
        $sql="select * from resource,resstatus,user where resource_status=resstatus_id and user_id=resource_resp and resource_id=" . $Resource;
        $res=mysql_query($sql) or die ($sql);
        $arrresource= mysql_fetch_assoc($res);
        $this->setResource($Resource);
        $this->setStartTime($arrresource['resource_starttime']);
        $this->setEndTime($arrresource['resource_stoptime']);
        self::$Resolution=$arrresource['resource_resolution']/60;
        $this->setMaxSlots($arrresource['resource_maxslots']);
        self::$ConfTolerance=$arrresource['resource_confirmtol'];
        $this->ResourceName=$arrresource['resource_name'];
        $this->Status=$arrresource['resource_status'];
        $this->StatusName=$arrresource['resstatus_name'];
        $this->Link=$arrresource['resource_wikilink'];
        $this->RespEmail=$arrresource['user_email'];
        $this->RespName=$arrresource['user_firstname']. " " . $arrresource['user_lastname'];
        $this->DelTolerance=$arrresource['resource_delhour'];
    }
    function setStartTime($arg) {$this->StartTime=$arg;$this->SlotStart=$this->StartTime;}
    function setEndTime($arg) {$this->EndTime=$arg;}
    function setResource($arg) {$this->Resource=$arg;}
    function setStartDate($arg) {$this->StartDate=$arg;}
    function setEntry($arg) {$this->Entry=$arg;}
    function setMaxSlots($arg) {$this->MaxSlots=$arg;}
    function setCalRepeat($arg) {$this->CalRepeat=$arg;}
    //function setResStatus($arg) {$this->ResStatus=$arg;}

    function getStartDate() {return $this->StartDate;}
    function getEntry() {return $this->Entry;}
    function getResource() {return $this->Resource;}
    function getStartTime() {return $this->StartTime;}
    function getEndTime() {return $this->EndTime;}
    function getMaxSlots() {return $this->MaxSlots;}
    function getCalRepeat() {return $this->CalRepeat;}
    function getResourceName() {return $this->ResourceName;}
    function getStatus() {return $this->Status;}
    function getStatusName() {return $this->StatusName;}
    function getLink() {return $this->Link;}
    function getRespEmail() {return $this->RespEmail;}
    function getRespName() {return $this->RespName;}
    function getDelTolerance() {return $this->DelTolerance;}
    
    public final static function getConfTolerance() {return self::$ConfTolerance;}
    public final static function getResolution() {return self::$Resolution;}
    function getResStatus() {return $this->ResStatus;}
    
/**
  * @author Nuno Moreno
  * @copyright 2009-2010 Nuno Moreno
  * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
  * @version 1.0
  * @abstract: Week drawer method
  * @param $resource
*/
    function draw_week(){
        $this->Slot = array_fill(0, ($this->EndTime-$this->SlotStart)/self::$Resolution, array_fill(0, 8, 0));
        $day=substr($this->StartDate,6,2);
        $month=substr($this->StartDate,4,2);
        $year=substr($this->StartDate,0,4);
        $weekahead=mktime(0,0,0,$month, $day+7,$year);
        $weekbefore=mktime(0,0,0,$month, $day-7,$year);
        
        echo "<table class=calendar id=caltable align=center><tr><th>";
        echo "<font size=1 >". date("M Y",mktime(0,0,0,$month,$day,$year));
        echo "<br><a href=weekview.php?resource=" . $this->getResource() . "&date=". date("Ymd",$weekbefore) . ">";
        echo "<img width=12px height=12px  src=pics/left.gif border=0>&nbsp;</a>";
        echo "<a href=weekview.php?resource=" . $this->getResource() . "&date=". date("Ym").(date("d")-date("N")) . ">";
        echo "<img width=10px src=pics/today.gif border=0>&nbsp;</a>";
        echo "<a href=weekview.php?resource=" . $this->getResource() . "&date=". date("Ymd",$weekahead) . ">";
        echo "<img width=12px height=12px src=pics/right.gif border=0></a>";
        
        echo "</th>"; 
        for ($i=1;$i<8;$i++) {
            $extra='';
            if (date('Ymd',mktime(0,0,0,$month,$day+$i,$year ))==date('Ymd')) $extra ="style='color:#bb3322'";
            echo "<th $extra>" . date("d-D",mktime(0,0,0,$month,$day+$i,$year)) . "</th>";
        }
        //echo "<th>" date("d", $this). "Monday</th>";
        //<th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th><th>Sunday</th>";
        $this->SlotStart=$this->StartTime;
        
        $nline=0;
        $ncells=0;
        while ($nline<($this->EndTime-$this->StartTime)/self::$Resolution) {
            echo "<tr>";
            $this->SlotStart=$this->StartTime+ self::$Resolution*$nline;
            $this->Duration=self::$Resolution;
            $from=floor($this->SlotStart) . "." . ($this->SlotStart - floor($this->SlotStart))*60;
            $to=floor($this->SlotStart + $this->Duration) . "." . ($this->SlotStart + $this->Duration-floor($this->SlotStart + $this->Duration))*60;
            $txt= number_format($from,2) . "-" . number_format($to,2);
            echo "<td align=center width=10% class=date >". $txt ."</td>\n";
                    
            for($weekday=1;$weekday<8;$weekday++){
                //if ($weekday==0) {
                    
                //} else {
                    //start day always a monday
                    $cell= new calCell;
                    $this->Day=date("Ymd",mktime(0,0,0,substr($this->StartDate,4,2),substr($this->StartDate,6,2)+$weekday,substr($this->StartDate,0,4)));
                    //echo $this->Day;
                    $sql= "select user_login,entry_id,entry_user,entry_repeat, entry_status, date_format(entry_datetime,'%H')+ date_format(entry_datetime,'%i')/60 time,entry_slots from entry,user where entry_status<>3 and entry_resource=" . $this->getResource() ." and user_id=entry_user and date_format(entry_datetime,'%Y%m%d')=". $this->Day . " and date_format(entry_datetime,'%H')+ date_format(entry_datetime,'%i')/60=" . $this->SlotStart . " order by entry_id";
                    $res=mysql_query($sql) or die ($sql);
                    $cell->setStartDate($this->Day);
                    $arr= mysql_fetch_assoc($res);
                    if ($arr['entry_id']!='') {
                        $cell->setNSlots($arr['entry_slots']);
                        $cell->setEntry($arr['entry_id']);
                        if ($arr['entry_repeat']==$this->CalRepeat) $cell->setRepeat($this->CalRepeat);
                        $cell->setUser($arr['user_login']);
                        $cell->setStartTime($this->SlotStart);
                        if (mysql_numrows($res)>1){
                            mysql_data_seek($res,1);
                            $arr= mysql_fetch_assoc($res);
                            //$cell->setStatus(4);
                            $cell->setNextUser($arr['user_login']);
                            //echo $arr['entry_id'];
                            $cell->setNextEntry($arr['entry_id']);
                        }
                        $cell->setEntryStatus($arr['entry_status']);
                        $cell->setTag("<a onmouseover=\"ShowContent('DisplayUserInfo'," . $cell->getEntry() . ")\" onmouseout=\"HideContent('DisplayUserInfo')\" href=weekview.php?resource=" . $this->Resource . "&entry=" . $cell->getEntry(). ">" . $cell->getUser() ."</a><br>" . 
                                      "<a onmouseover=\"ShowContent('DisplayUserInfo'," . $cell->getNextEntry() . ")\" onmouseout=\"HideContent('DisplayUserInfo')\" href=#>" . $cell->getNextUser() ."</a>" );
                        for ($j=0;$j<$cell->getNSlots();$j++) $this->Slot[$nline+$j][$weekday]=1;
                        //$nline=$nline+$cell->getNSlots()-1;
                        $cell->designslot(1);
                        $this->add($cell->getEntry(),$cell);
                    } else {
                        $cell->setNSlots(1);
                        $cell->setEntry('0');
                        $cell->setUser('');
                        $cell->setStartTime($this->SlotStart);
                        $cell->setTag('');
                        if ($this->Slot[$nline][$weekday]!=1) $cell->designSlot(0);
                        $this->add($ncells. "empty",$cell);
                    } //case it doesn't have an entry
                    //echo $cell->getEntry();
                    //$cell->designSlot();
                    
                    $ncells=$ncells+1;    
                //} //case not calendar hours
            } // end week days
            $nline=$nline+1;
            echo "</tr>";
        }
        echo "</table>";
    }
}

?>