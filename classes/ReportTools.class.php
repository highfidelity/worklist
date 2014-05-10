<?php

class ReportTools {
    function  getRollupColumn($columnName, $daysInRange) {
        $dateRangeType = 'd';
        $dateRangeQuery = "DATE_FORMAT(" .$columnName . ",'%Y-%m-%d') ";
        if($daysInRange > 31 && $daysInRange <= 180) {
            $dateRangeType = 'w';
            $dateRangeQuery = "yearweek(" .$columnName . ", 3) ";
        } else if($daysInRange > 180 && $daysInRange <= 365) {
            $dateRangeType = 'm';
            $dateRangeQuery = "DATE_FORMAT(" .$columnName . ",'%Y-%m') ";
        } else if($daysInRange > 365 && $daysInRange <= 730) {
            $dateRangeType = 'q';
            $dateRangeQuery = "concat(year(" .$columnName . "),QUARTER(" .$columnName . ")) ";
        } else if($daysInRange > 730) {
            $dateRangeType = 'y';
            $dateRangeQuery = "DATE_FORMAT(" .$columnName . ",'%Y') ";
        }
        return array('rollupRangeType' => $dateRangeType, 'rollupQuery' => $dateRangeQuery);
    }

    function  getMySQLDate($sourceDate) {
        if (empty($sourceDate)) $sourceDate = date('Y/m/d');
        $date_array = explode("/",$sourceDate); // split the array

        $targetDate = mktime(0, 0, 0, $date_array[0]  , $date_array[1], $date_array[2]);

        return date('Y-m-d',$targetDate);
    }

    /**
     * quarterByDate()
     *
     * Return numeric representation of a quarter from passed free-form date.
     *
     * @param mixed $date
     * @return integer
     */
    function quarterByDate($date) {
        return (int)floor(date('m', strtotime($date)) / 3.1) + 1;
    }

    /**
    * Fills a series with linear data, filling any gaps with null values.
    * The resulting array can directly be used in a chart assuming the labels use same data set.
    */
    function fillAndRollupSeries($strDateFrom, $strDateTo, $arySeries, $fillWithDate, $dateType = 'd') {
      $arySeriesData = array();
      $aryRollupData = array();
      $currentDate = mktime(0,0,0,substr($strDateFrom,5,2),  substr($strDateFrom,8,2), substr($strDateFrom,0,4));
      $toDate = mktime(0,0,0,substr($strDateTo,5,2),  substr($strDateTo,8,2), substr($strDateTo,0,4));
      $xLabels = array();
      $x1Labels = array();
      $x2Labels = array();
      $xFullLabels = array();
      $previousDate = $currentDate;
      while ($currentDate <= $toDate) {
        $x2Label = null;
        $xFullLabel = null;
        if($dateType == 'd') {
          $key = date('Y-m-d', $currentDate);
          $x1Label = date('d',$currentDate);
          $xFullLabel = date('m/d/Y', $currentDate);
          if(date('d',$currentDate) == '01' || sizeof($x1Labels) == 0) {
        $x2Label= date('M',$currentDate) ;
          }

          $currentDate = mktime(0,0,0,substr($key,5,2),  substr($key,8,2)+1, substr($key,0,4));
        } else if($dateType == 'w') {
          $key = date('oW', $currentDate);
          $weekStart = strtotime('+0 week mon', $currentDate);
          $weekEnd = strtotime('+0 week sun', $currentDate);
          if(date('m', $weekStart) == date('m', $weekEnd)) {
        $x1Label = date('d',$weekStart) ."-" . date('d',$weekEnd) ;
        $xFullLabel = date('M d',$weekStart) ." - " . date('d, Y',$weekEnd) ;
          } else {
        $x1Label = date('M d',$weekStart) ."-" . date('M d',$weekEnd) ;
        $xFullLabel = date('M d',$weekStart) ." - " . date('M d, Y',$weekEnd) ;
          }
          if (date('m',$weekStart) != date('m',$previousDate)) {
        $x2Label = date('M',$weekStart);
          }

          if(date('W',$currentDate) == '01' || sizeof($x1Labels) == 0) {
        $x2Label = date('M',$weekStart) . " " .date('Y',$currentDate) ;
          }
          // Store the current date as previous for identifying group changes
          $previousDate = $currentDate ;
          $currentDate = strtotime('+1 week', $weekStart);
        } else if($dateType == 'm') {
          $key = date('Y-m', $currentDate);
          $x1Label = date('M',$currentDate);
          if(date('m',$currentDate) == '01' || sizeof($x1Labels) == 0) {
        $x2Label = date('Y',$currentDate) ;
          }
          $xFullLabel = date('M Y',$currentDate);
          $currentDate = mktime(0,0,0,substr($key,5,2)+1,  1, substr($key,0,4));
        } else if($dateType == 'q') {
          $currentQuarter = self::quarterByDate(date('Y-m', $currentDate));
          $key = date('Y', $currentDate) . $currentQuarter;
          $x1Label  = date('Y', $currentDate) . ' Q' . $currentQuarter;
          $xFullLabel = $x1Label;
          $quarterStart = mktime(0,0,0, 1+ ($currentQuarter - 1) * 3,  1, substr($key,0,4));
          $currentDate = strtotime('+3 month', $quarterStart);
        } else if ($dateType == 'y') {
          $key = date('Y', $currentDate);
          $x1Label  = date('Y',$currentDate);
          $xFullLabel = $x1Label;
          $currentDate = mktime(0,0,0,1,  1, substr($key,0,4)+1);
        }

        if($fillWithDate) {
          $x1Labels[] = $x1Label;
          $x2Labels[] = $x2Label;
          $xFullLabels[]= $xFullLabel;
        } else if(isset($arySeries[$key])) {
        $arySeriesData[] = $arySeries[$key];
        } else {
        $arySeriesData[] = null;
        }
     }
      if($fillWithDate) {
        $arySeriesData = array('x1' => $x1Labels, 'x2' => $x2Labels, 'xFull' => $xFullLabels);
      }
      return $arySeriesData;
    }
}