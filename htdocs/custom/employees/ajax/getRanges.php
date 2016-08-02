<?php 

//Obtaining weeks of selected month
$monthSelected = $_POST['month'];
$yearSelected = $_POST['year'];

$arrayThursday = array();

$numberOfDays=cal_days_in_month(CAL_GREGORIAN,$monthSelected,2016);
for($i = 1; $i < $numberOfDays; $i++) {
    $dayOfTheWeek = jddayofweek ( cal_to_jd(CAL_GREGORIAN, $monthSelected, $i, $yearSelected), 0); //returns the day in an int from 0 to 7
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

    $ranges[$i]['from'] = str_pad($tempDayIterator, 2, "0", STR_PAD_LEFT).'-'.str_pad($monthSelected, 2, "0", STR_PAD_LEFT).'-'.$yearSelected;
    $ranges[$i]['to'] = str_pad($helpIterator, 2, "0", STR_PAD_LEFT).'-'.str_pad($monthSelected, 2, "0", STR_PAD_LEFT).'-'.$yearSelected;
    $i++;
    // $weekString = 'Del '.$tempDayIterator. 'al '.$helpIterator;
    // array_push($arrayWeekSelector,$weekString);
    $tempDayIterator = $helpIterator + 1;
}

if(end($arrayThursday) < $numberOfDays) {
    $ranges[$i]['from'] = str_pad($tempDayIterator, 2, "0", STR_PAD_LEFT).'-'.str_pad($monthSelected, 2, "0", STR_PAD_LEFT).'-'.$yearSelected;
    $ranges[$i]['to'] = str_pad($numberOfDays, 2, "0", STR_PAD_LEFT).'-'.str_pad($monthSelected, 2, "0", STR_PAD_LEFT).'-'.$yearSelected;
    // $weekString = 'Del '.$tempDayIterator. 'al '.$helpIterator;
    // array_push($arrayWeekSelector,$weekString);
}

echo json_encode($ranges);

?>