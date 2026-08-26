<?php 
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
//include_once($latest_skin_path."/moonday.php"); // 석봉운님의 음력날짜 함수

if (preg_match('/%/', $width)) {
  $col_width = "14%"; //표의 가로 폭이 100보다 크면 픽셀값입력
} else{
  $col_width = round($width/7); //표의 가로 폭이 100보다 작거나 같으면 백분율 값을 입력
}
$col_height= 30 ;//내용 들어갈 사각공간의 세로길이를 가로 폭과 같도록
$today = getdate(); 
$b_mon = $today['mon']; 
$b_day = $today['mday']; 
$b_year = $today['year']; 
if ($year < 1) { // 오늘의 달력 일때
  $month = $b_mon;
  $mday = $b_day;
  $year = $b_year;
}

if(!$year) 	$year = date("Y");
$file_index = $board_skin_path."/day"; ### 기념일 폴더 위치 지정

### 양력 기념일 파일 지정 : 해당년도 파일이 없으면 기본파일(solar.txt)을 불러온다
//$dayfile = file($file_index."/solar.txt");

$lastday=array(0,31,28,31,30,31,30,31,31,30,31,30,31);
if ($year%4 == 0) $lastday[2] = 29;
$dayoftheweek = date("w", mktime (0,0,0,$month,1,$year));

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);

if($month=="1")$month_m="JANUARY";
if($month=="2")$month_m="FEBRUARY";
if($month=="3")$month_m="MARCH";
if($month=="4")$month_m="APRIL";
if($month=="5")$month_m="MAY";
if($month=="6")$month_m="JUNE";
if($month=="7")$month_m="JULY";
if($month=="8")$month_m="AUGUST";
if($month=="9")$month_m="SEPTEMBER";
if($month=="10")$month_m="OCTOBER";
if($month=="11")$month_m="NOVEMBER";
if($month=="12")$month_m="DECEMBER";
?> 
<div class="sched-list">
<div class="cal-nav"> 
<h2 class="txt-point txt-center">
	<span title="<?php echo $year ?>년 <?php echo $month ?>월"><?php echo $month_m?> <?php echo $year?></span>
</h2> 
</div>
<table border="0" cellspacing="1" class="theme-list">
<thead>
  <tr align="center">     
	<th>S</th>
	<th>M</th>
	<th>T</th>
	<th>W</th>
	<th>T</th>
	<th>F</th>
	<th>S</th>
  </tr>
</thead>
<tbody>
<tr>
	<td colspan=7 style="height:10px;"></td>
</tr>
<?php 
$cday = 1;
$sel_mon = sprintf("%02d",$month);
$table_name="avo_write_".$bo_table;
$query = "SELECT * FROM {$table_name} WHERE wr_1!='' and left(wr_1,6) <= '$year$sel_mon' and (wr_2='' or left(wr_2,6) >= '$year$sel_mon') ORDER BY wr_id ASC";
$result = sql_query($query);
$j=0; // layer id
// 내용을 보여주는 부분
while ($row = sql_fetch_array($result)) {  // 제목글 뽑아서 링크 문자열 만들기..
  if( date('Ym',strtotime($row['wr_1'])) <  $year.$sel_mon ) {
	 $start_day =1; 
	 $start_day= (int)$start_day;
  } else {
	 $start_day = date('d',strtotime($row['wr_1']));
     $start_day= (int)$start_day;
  }
  if(!$row['wr_2']){
	$end_day = $start_day;
  } else if( date('Ym',strtotime($row['wr_2'])) >  $year.$sel_mon ) {
	 $end_day = $lastday[$month];
	 $end_day= (int)$end_day;
  } else {
	 $end_day = date('d',strtotime($row['wr_2']));
	 $end_day= (int)$end_day;
  }

	$d_e=date("w",strtotime($year.$sel_mon.sprintf("%02d",$end_day)));
	$w_e=date("W",strtotime($year.$sel_mon.sprintf("%02d",$end_day)));
	if($d_e==0) $w_e+=1;

  // 아이디에 따라 다른 아이콘이미지 출력 하고 싶을때 ///주석을 해제
  $imgown = 'icon';
  for ($i = $start_day ; $i <= $end_day;  $i++) {
    if (strlen($row['wr_3']) > 0) {  // 입력된 아이콘 값이 있을 때
      $imgown = $row['wr_3'] ;
	}
	 
	if($sc_id!=$row['wr_id']) $j++;
	
	$d=date("w",strtotime($year.$sel_mon.sprintf("%02d",$i)));
	$w=date("W",strtotime($year.$sel_mon.sprintf("%02d",$i)));
	if($d==0) $w+=1;

// subject length cut
	$subject = cut_str($row['wr_subject'],15);
	$starter="liner"; 
	if($i==$start_day) $starter.=" starter";
	if($i==$end_day) $starter.=" ender";
	if($d==0) $starter.=" first";
	if($d==6) $starter.=" last";

	$liner="";
	$style="";
	if(strstr($starter,"starter")){
		if($w_e==$w) $liner=$d_e-$d+1;
		else $liner=7-$d;
	}
	else if(strstr($starter,"first")){
		if($w_e==$w) $liner=$d_e-$d+1;
		else $liner=7-$d;
	}
	if($liner)
	$style="style='width:".($liner*100)."%;'";

    $html_day[$i].= '<a href="javascript:void(0);" class="txt-default '.$starter.'" data-day="'.$d.'" data-week="'.$w.'" data-idx="'.$j.'" '.$style.'>';
	if($i==$start_day) $html_day[$i].= '<p class="s_subject '.$imgown.'">'.$subject .'</p>';
	else $html_day[$i].= '<p class="s_subject '.$imgown.'"><span class="sound_only">'.$row['wr_subject'].'</span></p>';
	if($i==$start_day||$d==0){
	$html_day[$i].='<div class="popup_layer '.$imgown.'"><p class="popup_title">'.$row['wr_subject'].'</p>';
	$html_day[$i].='<p class="popup_cont">'.date("Y.m.d",strtotime($row['wr_1']));
	if(!$row['wr_2'] || $start_day!=$end_day) $html_day[$i].=' ~ ';
	if($row['wr_2'] && $start_day!=$end_day) $html_day[$i].=date("Y.m.d",strtotime($row['wr_2']));
	$html_day[$i].='<br>'.$row['wr_10'].'</p></div>';
	}
	$html_day[$i].='</a>'; 
	
	$sc_id = $row['wr_id'];
	}
} 

  // 달력의 틀을 보여주는 부분

  $temp = 7- (($lastday[$month]+$dayoftheweek)%7);

  if ($temp == 7) $temp = 0;
     $lastcount = $lastday[$month]+$dayoftheweek + $temp;

  for ($iz = 1; $iz <= $lastcount; $iz++) { // 42번을 칠하게 된다.
    $bgcolor = "days";  // 쭉 흰색으로 칠하고
    if ($b_year==$year && $b_mon==$month && $b_day==$cday) $bgcolor = "today"; 
	$re=$iz%7;
    if ($re == 1) echo ("<tr>"); // 주당 7개씩 한쎌씩을 쌓는다.
    if ($dayoftheweek < $iz  &&  $iz <= $lastday[$month]+$dayoftheweek)	{
	// 전체 루프안에서 숫자가 들어가는 셀들만 해당됨
	// 즉 11월 달에서 1일부터 30 일까지만 해당
	$daytext = "$cday";   // $cday 는 숫자 예> 11월달은 1~ 30일 까지
	//$daytext 은 셀에 써질 날짜 숫자 넣을 공간

	// 여기까지 숫자와 들어갈 내용에 대한 변수들의 세팅이 끝나고 
	// 이제 여기 부터 직접 셀이 그려지면서 그 안에 내용이 들어 간다.
	if($re==0) $col="right";
	else if($re==1) $col="left";
	else $col="";
	$fr_date = $year.sprintf("%02d",$month).sprintf("%02d",$cday);
	 
	echo ("<td width=$col_width height=$col_height class='{$bgcolor} {$col}' valign='top'>");


	// 기념일 파일 내용 비교위한 변수 선언, 월과 일을 두자리 포맷으로 고정
	if (strlen($month) == 1) { 
		$monthp = "0".$month ;
	} else {
		$monthp = $month ; 
	}
	if (strlen($cday) == 1) {
		$cdayp = "0".$cday ;
	} else { 
		$cdayp = $cday ; 
	}
	$memday = $year.$monthp.$cdayp;
	$daycont = "" ;

	// 기념일(양력) 표시
	/*
	for($i=0 ; $i < sizeof($dayfile) ; $i++) {  // 파일 첫 행부터 끝행까지 루프
		$arrDay = explode("|", $dayfile[$i]);
		if($memday == $year.$arrDay[0]) {
			$daycont = $arrDay[1]; 
			$daycontcolor = $arrDay[2];
			if(substr($arrDay[2],0,3)=="red") $daycolor = "red"; // 공휴일은 날짜를 빨간색으로 표시
		}
	}
	*/
	/*
    // 석봉운님의 음력날짜 변수선언
    $myarray = soltolun($year,$month,$cday);
    if ($myarray[day]==1 || $myarray[day]==11 || $myarray[day]==21) {
      $moonday ="<font color='gray'>&nbsp;(음)$myarray[month].$myarray[day]$myarray[leap]</font>";
    } else {
      $moonday="";
    }
	
	include($file_index."/lunar.txt"); ### 음력 기념일 파일 지정

    if ($annivmoonday&&$daycont) $blank="<br />"; // 음력절기와 양력기념일이 동시에 있으면 한칸 띔
    else $blank="";
	*/  
	echo "<i>".$daytext."</i>";
	echo $html_day[$cday];

    echo ("</td>");  // 한칸을 마무리
    $cday++; // 날짜를 카운팅
  } 
  // 유효날짜가 아니면 그냥 회색을 칠한다.
  else { echo ("<td width=$col_width height=$col_height class='noday'>&nbsp;</td>"); }
  if ($re == 0) echo ("</tr>");
   
} // 반복구문이 끝남
?>
</tbody>
</table>
</div>