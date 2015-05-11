<?php
/*************************************************************
*Name: Patrick Levy
*Class: CS290 Web Development
*Assignment4-Part2
*Last Modified: 5/8/2015
*************************************************************/

include 'storedInfo.php';
//**********************************************************
//Connect to database
//**********************************************************
$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "levyp-db", $myPassword, "levyp-db");
if ($mysqli->connect_errno) {
    //echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
else {
//	echo "connected!<br>";
}
//**********************************************************
//Check if database exists and if not create it
//**********************************************************
if (!$mysqli->query("DROP TABLE IF EXISTS test") ||
    !$mysqli->query("CREATE TABLE movieDatabase(id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255) UNIQUE,
    				 category VARCHAR(255), length INT, rented INT    )")  ) {   
	//echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
}

/********************************************************************
//GET and POST Data for update of database
********************************************************************/
class data {
	function __construct($type){
		$this->Type = $type;
	}
	public $Type;
	public $parameters = array();

}
//GET
$getData = new data('GET');

if ($_GET){
	foreach($_GET as $key => $value){
		$getData->parameters[$key] = $value;
	}
}
else {
	$getData->parameters = null;
}

//POST
$postData = new data('POST');

if ($_POST){
	foreach($_POST as $key => $value){
		$postData->parameters[$key] = $value;
	}
}
else {
	$postData->parameters = null;
}

/**************************************************************************
//Update database based on POST data
**************************************************************************/

//New Video to be added to database
$errorVidName = array("Please enter a video name as a string of up to 255 characters", false);
$errorVidCategory = array("Please enter a category as a string of up to 255 characters", false);
$errorVidLength = array("Please enter a length as a positive number.", false);


if ($_POST[newVidName] != null){
	$newName = $_POST[newVidName];
	$newCategory = $_POST[newVidCategory];
	$newLength = $_POST[newVidLength];
	$newRented = 0;

	//Check that the name was properly input
	if (!is_string($newName) || strlen($newName) > 255 || strlen($newName) <= 0){
		$errorVidName[1] = true;
	}

	//Check that the length was properly input
	if ($_POST[newVidLength] != null){
		if (!is_numeric($newLength)) {
			$errorVidLength[1] = true;
		}
		if ( (int)$newLength <= 0){
			$errorVidLength[1] = true;
		}
	}

	//Add new video to database if no input errors were found
	if ($errorVidName[1] == false && $errorVidCategory[1] == false && $errorVidLength[1] == false) {
		if (!($stmt = $mysqli->prepare("INSERT INTO movieDatabase(name, category, length, rented) VALUES (?, ?, ?, ?)"))) {
			//echo "Prepare failed: (" . $mysqli->errno .") " . $mysqli->error;	
		}
		if (!($stmt->bind_param("ssii", $_POST[newVidName], $_POST[newVidCategory], $_POST[newVidLength], $newRented))) {
			//echo "Binding Parameters failed: (" . $stmt->errno . ") " . $stmt->error;
		}
		if (!($stmt->execute())) {
			//echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
		}
	}
}

//Delete All Videos
if ($_POST[delete] == "deleteAllVideos"){
	$mysqli->query("DROP TABLE IF EXISTS movieDatabase");
	header ('Location: moviedb.php');
}

//Delete video
if ($_POST[delete] != null){
	$idDelete = $_POST[delete];
	$stmt = $mysqli->prepare("DELETE FROM movieDatabase WHERE id = ?");
	$stmt->bind_param("i", $idDelete);
	$stmt->execute();
}

//Check in/out videos
if ($_POST[rentChange] != null){
	$idCheckInOut = $_POST[rentChange];
	//this should be updated to 1)prepare 2)bind 3)execute
	$selectionIsRented = $mysqli->query("SELECT rented FROM movieDatabase WHERE id = '".$idCheckInOut."' ")->fetch_object()->rented;
	if ($selectionIsRented == 0){
		$mysqli->query("UPDATE movieDatabase SET rented = 1 WHERE (id = '".$idCheckInOut."' && rented = 0) ");
	}
	else {
		$mysqli->query("UPDATE movieDatabase SET rented = 0 WHERE (id = '".$idCheckInOut."' && rented = 1) ");
	}
}
//Filter Results
if (($_GET[filter] != null) && ($_GET[filter] != 'All')) { 
	$filterBy = $_GET[filter];
	$inventory = $mysqli->query("SELECT id, name, category, length, rented FROM movieDatabase 
		                         WHERE category = '".$filterBy."' ");
}
else {
	$inventory = $mysqli->query("SELECT id, name, category, length, rented FROM movieDatabase");
}

/************************************************
//Read category types from database
************************************************/
$categoryList = array();
$categories = $mysqli->query("SELECT DISTINCT category FROM movieDatabase");
while ($currentCat = $categories->fetch_assoc()) {
	if ($currentCat["category"] != "" || $currentCat["category"] != null){
		array_push($categoryList, $currentCat["category"]);
	}
}

/***************************************************
//Title
***************************************************/
echo 
'<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Video Rental</title>
</head>
<body>
<h1>Video Rental Database</h1>';

/****************************************************
//Dropdown for filtering results
*****************************************************/

echo '<h2>Current Inventory</h2><p>';
echo '<table>';
echo '<tr>';
echo '<td>Filter by category:</td>';
echo '<td>'; 
echo '<form action="" method="get">';
echo '<select name="filter">';
echo   '<option value="All">All</option>';
foreach ($categoryList as &$type){
	echo '<option value=' . $type . '>'.$type.'</option>';
}
unset($type); 
		
echo  '</select>
	  <td>
	  <input type="submit" value="Submit">
	  </td>
	  </form>';	
echo '</table>';

echo '<h4>Currently filtering by: ' . $_GET[filter] . '</h4>';

/*****************************************************
//Output current database
//Citation:  http://www.sanwebe.com/2013/03/basic-php-mysqli-usage
******************************************************/
//Print header row
echo '<table border="1">';
echo '<tr>';
echo '<th>' . 'ID' . '</th>';
echo '<th>' . 'Name' . '</th>';
echo '<th>' . 'Category' . '</th>';
echo '<th>' . 'Length' . '</th>';
echo '<th>' . 'Status' . '</th>';
echo '<th>' . 'Check In/Out' . '</th>';
echo '<th>' . 'Delete Video' . '</th>';

//Print items in database
while ($row = $inventory->fetch_assoc()) {
	
	$id = $row["id"];
	echo '<tr>';
	echo '<td>' . $id . '</td>';
	echo '<td>' . $row["name"] . '</td>';
	echo '<td>' . $row["category"] . '</td>';
	
	if ($row["length"] != 0){
		echo '<td>' . $row["length"] . '</td>';
	}
	else{
		echo '<td>' . '</td>';
	}
	if ($row["rented"] == 1){
		echo '<td>' . 'checked out' . '</td>';
	}
	else {
		echo '<td>' . 'available' . '</td>'; 
	}
	echo '<td>' . 
		 '<form action = "" method="post">' .
		   "<button name='rentChange' value='".$id."'>Check In/Out</button>" .
		 '</form>' . 
		 '</td>';

	echo '<td>' . 
		 '<form action = "" method="post">' .
		   "<button name='delete' value='".$id."'>Delete Video</button>" .
		 '</form>' . 
		 '</td>';
	echo '</tr>';
}
echo '</table>';
echo '</body>
	  </html>';

/****************************************************
//Add Video Interface
**************************************************/
echo '<p>';
echo '<h3>Add New Video</h3><p>';

//Check for previous input errors
if ($errorVidName[1] == true){
	echo $errorVidName[0];
	echo '<p>';
}
if ($errorVidCategory[1] == true){
	echo $errorVidCategory[0];
	echo '<p>';
}
if ($errorVidLength[1] == true){
	echo $errorVidLength[0];
	echo '<p>';
}

//Display table for inputting new video text information
echo '<table border="1">';
echo '<tr>';
echo '<form method="post">' .
	  '<td>' . '<input type="text" name="newVidName" placeholder="New Video Name" required>' . '</td>'.
	  '<td>' . '<input type="text" name="newVidCategory" placeholder="New Video Category">' . '</td>' .
	  '<td>' . '<input type="text" name="newVidLength" placeholder="New Video Length (minutes)">' . '</td>' .
	  '<td>' . '<input type="submit" value="Add New Video">' . '</td>' .
	  '</form>' .
	  '</table>';

/********************************************************
//Delete All Videos
*******************************************************/
echo '<p>';
echo  '<form action = "" method="post">' .
	    "<button name='delete' value='deleteAllVideos'>Delete All Videos</button>" .
      '</form>';  
?>