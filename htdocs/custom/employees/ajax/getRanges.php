<?php 

//Obtaining weeks of selected month
$monthSelectedTest = $_POST['month'];
$yearSelectedTest = $_POST['year'];

$arrayThursday = array();

$numberOfDays=cal_days_in_month(CAL_GREGORIAN,$monthSelectedTest,2016);
for($i = 1; $i < $numberOfDays; $i++) {
    $dayOfTheWeek = jddayofweek ( cal_to_jd(CAL_GREGORIAN, $monthSelectedTest, $i, $yearSelectedTest), 0); //returns the day in an int from 0 to 7
    if($dayOfTheWeek == 4) {
        array_push($arrayThursday, $i);
    }
}

$tempDayIterator = 1;

if($tempDayIterator == $arrayThursday[0]) {
    $tempDayIterator++;
    unset($arrayThursday[0]);
}

$arrayWeekSelector = array();

$i = 0;

foreach ($arrayThursday as $helpIterator) {    
    $ranges[$i]['from'] = $tempDayIterator;
    $ranges[$i]['to'] = $helpIterator;
    $i++;
    // $weekString = 'Del '.$tempDayIterator. 'al '.$helpIterator;
    // array_push($arrayWeekSelector,$weekString);
    $tempDayIterator = $helpIterator + 1;
}

if(end($arrayThursday) < $numberOfDays) {
    $ranges[$i]['from'] = $tempDayIterator;
    $ranges[$i]['to'] = $numberOfDays;
    // $weekString = 'Del '.$tempDayIterator. 'al '.$helpIterator;
    // array_push($arrayWeekSelector,$weekString);
}

echo json_encode($ranges);

?>