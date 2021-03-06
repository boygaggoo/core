<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonCourseID=$_GET["gibbonCourseID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/course_manage_delete.php&gibbonCourseID=" . $gibbonCourseID . "&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] ;
$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/course_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/course_manage_delete.php")==FALSE) {
	//Fail 0
	$URL.="&deleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonCourseID=="") {
		//Fail1
		$URL.="&deleteReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonCourseID"=>$gibbonCourseID); 
			$sql="SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&deleteReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&deleteReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Try to delete entries in gibbonTTDayRowClass
			try {
				$dataSelect=array("gibbonCourseID"=>$gibbonCourseID); 
				$sqlSelect="SELECT gibbonTTDayRowClassID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseID=:gibbonCourseID" ;
				$resultSelect=$connection2->prepare($sqlSelect);
				$resultSelect->execute($dataSelect);
			}
			catch(PDOException $e) { }
			if ($resultSelect->rowCount()>0) {
				while ($rowSelect=$resultSelect->fetch()) {
					try {
						$dataDelete=array("gibbonTTDayRowClassID"=>$rowSelect["gibbonTTDayRowClassID"]); 
						$sqlDelete="DELETE FROM gibbonTTDayRowClassException WHERE gibbonTTDayRowClassID=:gibbonTTDayRowClassID" ;
						$resultDelete=$connection2->prepare($sqlDelete);
						$resultDelete->execute($dataDelete);
					}
					catch(PDOException $e) { }
				}
			}
			
			try {
				$dataSelect=array("gibbonCourseID"=>$gibbonCourseID); 
				$sqlSelect="SELECT gibbonCourseClassID FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID" ;
				$resultSelect=$connection2->prepare($sqlSelect);
				$resultSelect->execute($dataSelect);
			}
			catch(PDOException $e) { }
			if ($resultSelect->rowCount()>0) {
				while ($rowSelect=$resultSelect->fetch()) {
					try {
						$dataDelete=array("gibbonCourseClassID"=>$rowSelect["gibbonCourseClassID"]); 
						$sqlDelete="DELETE FROM gibbonTTDayRowClass WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
						$resultDelete=$connection2->prepare($sqlDelete);
						$resultDelete->execute($dataDelete);
					}
					catch(PDOException $e) { }
				}
			}
			
			//Delete students
			try {
				$dataStudent=array("gibbonCourseID"=>$gibbonCourseID); 
				$sqlStudent="SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID" ;
				$resultStudent=$connection2->prepare($sqlStudent);
				$resultStudent->execute($dataStudent);
			}
			catch(PDOException $e) { }
			while ($rowStudent=$resultStudent->fetch()) {
				try {
					$dataDelete=array("gibbonCourseClassID"=>$rowStudent["gibbonCourseClassID"]); 
					$sqlDelete="DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
					$resultDelete=$connection2->prepare($sqlDelete);
					$resultDelete->execute($dataDelete);
				}
				catch(PDOException $e) { }
			}
			
			//Delete classes
			try {
				$dataDelete=array("gibbonCourseID"=>$gibbonCourseID); 
				$sqlDelete="DELETE FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}

			//Delete Course
			try {
				$dataDelete=array("gibbonCourseID"=>$gibbonCourseID); 
				$sqlDelete="DELETE FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			//Success 0
			$URLDelete=$URLDelete . "&deleteReturn=success0" ;
			header("Location: {$URLDelete}");
		}
	}
}
?>