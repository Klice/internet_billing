<?php

pg_connect("dbname=vpn user=vpn password=vpn host=localhost");
ini_set("memory_limit","10M");
if (isset($_COOKIE['sid'])) {
	$username=get_name($_COOKIE['sid']);
	if ($username) {
		select_act($username);
	}
}

if (isset($_POST['submit'])) {process_form();}

display_form();


function get_name($sid)
{
	$name="";
	$ret=pg_exec("SELECT users.user_name FROM users,web_sess WHERE users.id=web_sess.uid AND web_sess.sid='$sid'");
	if (pg_numrows($ret)>0) {
		$name=pg_result($ret,0,0);
		$ret=pg_exec("UPDATE web_sess SET time=now() WHERE web_sess.sid='$sid'");
	}
#	print "------>".$name;
	return $name;
}

function select_act() {
	if (isset($_GET['act'])) { $act=$_GET['act']; } else {$act="";}
	if ($act=="exit") {act_exit();}
	if ($act=="stat") {act_stat();}
	if ($act=="pay") {act_pay();}
	if ($act=="correct") {act_cor();}
	if ($act=="exch") {act_exch();}
	if ($act=="list") {act_list();}
	if ($act=="detail") {act_detail();}
	if ($act=="show_user") {act_show_user();}
	display_stat(get_name($_COOKIE['sid']));
}

function act_show_user() {
	if (isset($_GET['id'])) { $uid=$_GET['id']; } else {$uid="";}
	if ($uid) {
		if (chk_access()){
			display_top();
			show_user($uid);
			display_bot();
		}
	}
}

function act_list() {
	if (chk_access()){
		display_top();
		show_users(); 
		display_bot();
	}
}

function show_user($id) {
	$ret=pg_exec("SELECT users.id, users.user_name, users.passwd, users.descr, tarif.name, tarif.price, tarif.price_local FROM users,tarif WHERE users.id=$id AND tarif.id=users.tarif");
	?>
<TABLE>
	<TR>
		<TD colspan=2>Личная каточка</TD>
	</TR>
	<TR>
		<TD>ID</TD>
		<TD><?echo pg_result($ret,0,0);?></TD>
	</TR>
	<TR>
		<TD>Имя</TD>
		<TD><?echo pg_result($ret,0,1);?></TD>
	</TR>
	<TR>
		<TD>Пароль</TD>
		<TD><?echo pg_result($ret,0,2);?></TD>
	</TR>
	<TR>
		<TD>Описание</TD>
		<TD><?echo pg_result($ret,0,3);?></TD>
	</TR>
</TABLE>
<TABLE>
<TR>
	<TD colspan=2>Свойства</TD>
</TR>
<TR>
	<TD>Тариф</TD>
	<TD><?echo pg_result($ret,0,4);?><br>
		Интер.: <?echo pg_result($ret,0,5);?> р.<br>
		Лок.: <?echo pg_result($ret,0,6);?> р.
	</TD>
</TR>
<TR>
	<TD></TD>
	<TD></TD>
</TR>
<TR>
	<TD></TD>
	<TD></TD>
</TR>
</TABLE>
	<?
}


function show_users() {
	$sum_m_i=0;
	$sum_m_g=0;
	$sum_r=0;
	$sum_b=0;
	$q="SELECT * FROM users";
	$ret=pg_exec($q);
	?>
	<TABLE class=gnav align=center>
	<tr><td>Пользователь</td><td colspan=3 align=center>Месяц</td><td align=center>Остаток</td></tr>
	<tr><td></td><td>Мб. Инт.</td><td>Мб. Гор.</td><td>Руб.</td><td>Руб.</td></tr>
	<?
	for($i=0;$i<pg_numrows($ret);$i++) {
		$uid=pg_result($ret,$i,0);
		$uname=pg_result($ret,$i,1);
		$uact=pg_result($ret,$i,4);

		$ulimit=pg_result($ret,$i,1);
		$usum=pg_result($ret,$i,3);
		$inet_traf=pg_result($ret,$i,7);
		$inet_price=pg_result($ret,$i,4);
		$local_traf=pg_result($ret,$i,8);
		$local_price=pg_result($ret,$i,5); 
	?>
	<tr <?if ($uact=="f") {print "bgcolor=#FF7000";}?>>
		<td><?echo $uname;?></td>
		<td><?printf ("%.2f Мб",$inet_traf);?></td>
		<td><?printf ("%.2f Мб",$local_traf);?></td>
		<td><?printf ("%.2f р.",$inet_traf*$inet_price+$local_traf*$local_price);?></td>
		<td align=center <?if ($ulimit<=0) {print "bgcolor=#FF0F00";}?>><?printf ("%.2f р.",$ulimit*$inet_price);?><font size=-10> <?printf("%.2f",$usum);?> р.<font></td>
		<td><a href=./?act=show_user&id=<?echo $uid;?>>Параметры</a></td>
	</tr>
	<?
		$sum_m_i+=$inet_traf;
		$sum_m_g+=$local_traf;
		$sum_r+=$inet_traf*$inet_price+$local_traf*$local_price;
		$sum_r_s+=$inet_traf*1.8+$local_traf*0.09;
		$sum_b+=$ulimit*$inet_price;
	}
	?>
	<tr>
		<td>Всего</td>
		<td><?printf("%.2f Мб",$sum_m_i);?></td>
		<td><?printf("%.2f Мб",$sum_m_g);?></td>
		<td><?printf("%.2f р.",$sum_r);?></td>
		<td><?printf("%.2f р.",$sum_b);?></td>
	</tr>
	<tr>
		<td></td>
		<td></td>
		<td>Себ.</td>
		<td><?printf("%.2f р.",$sum_r_s);?></td>
		<td></td>
	</tr>
	<tr>
		<td></td>
		<td></td>
		<td>Моржа.</td>
		<td><?printf("%.2f р.",$sum_r-$sum_r_s);?></td>
		<td></td>
	</tr>
	</table>
	<?
}

function act_exit() {
	setcookie("sid",time()+60*60*24*30);
	unset($act);
	display_self();
}

function act_exch() {
?>
<html>
<head>
<title>[Обмен]</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<META HTTP-EQUIV="Expires" CONTENT="Mon, 25 Sep 2001 00:02:01 GMT">
<link rel="stylesheet" href="/stat/ht/all.css" type="text/css">
<script language=JavaScript1.2>
val=null;
function changeval() {
	if(mf.inp.value==NaN){
	mf.inp.value=val;
	}
	else {
	val=mf.inp.value
	}
}
</script>

</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="bgmain"> 
<style type="text/css">
	.plain {height:20px; vertical-align:middle;}
</style> 
<?
	if ($_POST['exchange']) {
	do_exch();
	} else {
	show_exch();
	}
?>
</table>
</form>

</body>
</html>
<?
	exit;
}

function do_exch () {
	$user=$_POST['user'];
	$sum=$_POST['sum'];
	$orig_user=get_name($_COOKIE['sid']);
	$ret=pg_exec("SELECT param, tarif.price FROM tarif, users_attribute, users WHERE attr = 'Traffic-Limit' AND users_attribute.user_name='$orig_user' and users.user_name='$orig_user' and users.tarif=tarif.id");
	$orig_sum=pg_result($ret,0,0)*pg_result($ret,0,1)/1000000;
	$orig_price=pg_result($ret,0,1);
	$ret = pg_exec("select user_name from users where user_name='$user'");
	if (pg_numrows($ret)<1) {print "<br><p class=gnav align=center><font class=err>ОШИБКА!</font> Неверное имя пользователя.</p>";exit;}
	if (!is_numeric($sum)) {print "<br><p class=gnav align=center><font class=err>ОШИБКА!</font> Неверная сумма.</p>";exit;} 
	if ($sum<0) {print "<br><p class=gnav align=center><font class=err>ОШИБКА!</font> Сумма меньше нуля.</p>";exit;}
	if ($sum>$orig_sum) {print "<br><p class=gnav align=center><font class=err>ОШИБКА!</font> Не достаточно средств на счете.</p>";exit;}
	pg_exec("SELECT add_money(users.id,$sum) FROM users WHERE users.user_name='$user'");
	pg_exec("SELECT add_money(users.id,-$sum) FROM users WHERE users.user_name='$orig_user'");
?>
	<br><p class=gnav align=center><font color=green>ГОТОВО!</font><br><br>
	Трафик на сумму: <font class=err><?echo $sum;?></font> руб. (<?printf ("%.2f",$sum/$orig_price);?> Мб)<br>
	Передан пользователю: <font class=err><?echo $user;?></font>
<?
}

function show_exch() {
?>
<p class=copyright>Тут вы можете обменяться трафиком с другим пользователем интернета, для этого укажите имя пользователя которому вы хотите передать трафик и сумму в рублях:</font></p>
<form method=post target="<?echo $PHP_SELF;?>">
<table class=gnav align=center>
	<tr>
	<td>Имя пользователя:</td>
	<td><INPUT TYPE=TEXT name='user' size=16 maxlength=16></td>
	</tr>
	<tr>
	<td>Сумма:</td>
	<td><INPUT TYPE=TEXT name='sum' size=3 maxlength=3 onkeydown=chngeval()> руб.</td>
	</tr>
	<tr align=center>
	<td colspan=2><INPUT TYPE=submit name='exchange' value='Передать'></td>
	</tr>
<?    
}

function act_pay() {
	display_top();
	if (file_exists ("/usr/local/scripts/lock/pay.lck")) {
	display_error(4);
	}
	if (isset($_POST['pay'])) {
	$user=get_name($_COOKIE['sid']);
	$serial=$_POST['serial'];
	$pin=$_POST['pin'];
	$cmd="./cgi-bin/pay.pl -n $serial -p $pin -u $user";
	$res=shell_exec($cmd);
	if ($res==0){ display_error(2);}
	if ($res<0){ display_error(3);}
	if ($res>0) {
		?>
<p class=gnav align=center>Платеж на сумму <?echo $res?>р. принят.</p>
<?
		display_bot(0);
		exit;
	}
	display_error(3);
	} else {
	display_pay();
	}
	display_bot();
}

function act_stat() {
	display_top();
	if (isset($_POST['showstat'])) {
		display_statform();
		$start=date_tosql($_POST['dc1'])." 00:00:00";
		$stop=date_tosql($_POST['dc2'])." 23:59:59";
		$traf_type=$_POST['traf_type'];
//		$traf_sess=$_POST['traf_sess'];
		$sort=$_POST['sort_by'];
		if (chk_access()) {
			$user=$_POST['user_name'];
		} else {
			$user=get_name($_COOKIE['sid']);
		}
		if (($user=="All")&&(chk_access())) {$user="";}
		if ($sort==4) {
			if (chk_access()) {
				show_stat_users($start,$stop,$traf_type);
			}
		}
		if ($sort==5) {
			if (chk_access()) {
				show_stat_ip($start,$stop,$traf_type);
			}
		} 
		if ($sort<4) {show_stat($start,$stop,$sort,$user,$traf_type);}
	} else { display_statform(); }

	display_bot();
}

function act_detail() {
	display_top();
	if (chk_access()) {
		if ($_POST['showstat_detail']) {
			display_statform_detail();
			$start=date_tosql($_POST['dc1']);
			$stop=date_tosql($_POST['dc2']);
			$sort=$_POST['sort_by'];
			$user=$_POST['ip_address'];
			show_stat_detail($start,$stop,$sort,$user);
		}
		else { display_statform_detail(); }
	}
	display_bot();
}


function show_stat_detail($start,$stop,$sort,$user) {
	$date_parts=split('-',$start);
	$date="$date_parts[2]-$date_parts[1]-$date_parts[0]";
	
}

function date_tosql ($date) {
	$date_parts=split('-',$date);
	$date="$date_parts[2]-$date_parts[1]-$date_parts[0]";
	return $date;
}

function display_statform () {
	global $PHP_SELF;
	if (isset($_POST['dc1'])) {$start=$_POST['dc1'];} else { $start=date('01-m-Y'); }
	if (isset($_POST['dc2'])) {$stop=$_POST['dc2'];} else { $stop=date('d-m-Y'); }
	if (isset($_POST['sort_by'])) {$sort_by=$_POST['sort_by'];} else {$sort_by=2;}
	if (isset($_POST['traf_type'])) {$traf_type=$_POST['traf_type'];} else {$traf_type=0;}
	?>
<form name="demoform" method=post target="<? echo $PHP_SELF;?>">
  <table border=0 align=center class=gnav>
	<tr>
	  <td colspan="2">С:
		<input class="plain" name="dc1" value="<? echo $start?>" size="12" onfocus="this.blur()" readonly>
		<a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fStartPop(document.demoform.dc1,document.demoform.dc2);return false;" HIDEFOCUS><img class="PopcalTrigger" align="absmiddle" src="images/trinux-sb_r4_c2.gif" width=16 height=16 border="0" alt=""></a>	Пo:
		<input class="plain" name="dc2" value="<? echo $stop?>" size="12" onfocus="this.blur()" readonly>
		<a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fEndPop(document.demoform.dc1,document.demoform.dc2);return false;" HIDEFOCUS><img class="PopcalTrigger" align="absmiddle" src="images/trinux-sb_r4_c2.gif"  width=16 height=16 border="0" alt=""></a>
		<input TYPE=submit name="showstat" value="Показать"></td>
	</tr>
<!--	<tr>
		<td valign="top" align="center">Трафик:</td>
		<td>
			<input name="traf_type" type="radio" value="0"<?if ($traf_type==0) { echo 'checked';}?>>Интеренет<br>
			<input name="traf_type" type="radio" value="1"<?if ($traf_type==1) { echo 'checked';}?>>Городской<br> 
			<input name="traf_sess" type="checkbox" <?//if ($_POST['traf_sess']==1) { echo checked;}?>>По сессиям
		</td>
	</tr> -->
	<input name="traf_type" type="hidden" value="0">
	<tr align=left>
	  <td valign="top" align="center">Группировать по:</td>
	  <td>Месяцам
		<input name="sort_by" type="radio" value="3"<?if ($sort_by==3) { echo 'checked';}?>>
		<br>Дням
		<input name="sort_by" type="radio" value="2" <?if ($sort_by==2) { echo 'checked';}?>>
		<br>Времени
		<input name="sort_by" type="radio" value="1"<?if ($sort_by==1) { echo 'checked';}?>>
	<?if (chk_access()) {?>
		<br>Пользователям
		<input name="sort_by" type="radio" value="4"<?if ($sort_by==4) { echo 'checked';}?>>
	<?}?>
	</td></tr>
	<?if (chk_access()) {
	$ret = pg_exec("select user_name from statnew group by user_name order by user_name ASC");
	?>
 <tr>
	<td align="right">Статистика по пользователю:</td>
	<td>
	<SELECT name="user_name">
	<option value="All">-Все-</option>
	<?
	$uname=get_name($_COOKIE['sid']);
	if (isset($_POST['user_name'])) {$uname=$_POST['user_name'];} 
	for($i=0;$i<pg_numrows($ret);$i++) { 
	$name=pg_result($ret,$i,0);?>
	<option value="<?echo $name?>" <?if ($name==$uname) {echo "SELECTED";}?>><?echo $name?></option>
	<?}?>
	</SELECT>
	</td>
 </tr>
	<?}?>
  </table>
</form>
<?
}

function display_statform_detail () {
	if ($_POST['dc1']) {$start=$_POST['dc1'];} else { $start=date('01-m-Y'); }
	if ($_POST['dc2']) {$stop=$_POST['dc2'];} else { $stop=date('d-m-Y'); }
	?>
<form name="demoform" method=post target="<? echo $PHP_SELF;?>">
  <table border=0 align=center class=gnav>
	<tr><td align=right>IP Адрес: </td><td><input name="ip_address" type=text value="<?echo $_POST['ip_address'];?>"></td></tr>
	<tr>
	  <td colspan="2">С:
		<input class="plain" name="dc1" value="<? echo $start?>" size="12" onfocus="this.blur()" readonly>
		<a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fStartPop(document.demoform.dc1,document.demoform.dc2);return false;" HIDEFOCUS><img class="PopcalTrigger" align="absmiddle" src="images/trinux-sb_r4_c2.gif" width=16 height=16 border="0" alt=""></a>	Пo:
		<input class="plain" name="dc2" value="<? echo $stop?>" size="12" onfocus="this.blur()" readonly>
		<a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fEndPop(document.demoform.dc1,document.demoform.dc2);return false;" HIDEFOCUS><img class="PopcalTrigger" align="absmiddle" src="images/trinux-sb_r4_c2.gif"  width=16 height=16 border="0" alt=""></a>
		<input TYPE=submit name="showstat_detail" value="Показать"></td>
	</tr>
	<tr align=left>
	  <td valign="top" align="center">Группировать по:</td>
	  <td>Адресам
		<input name="sort_by" type="radio" value="3"<?if ($_POST['sort_by']==3) { echo checked;}?>>
		<br>
		Адресам и портам
		<input name="sort_by" type="radio" value="2" <?if (($_POST['sort_by']==2)||(!$_POST['sort_by'])) { echo checked;}?>>
	</td></tr>
  </table>
</form>
<?
}

function display_form() {
	global $PHP_SELF;
	display_top();
	?>
<form method=post target="<?echo $PHP_SELF;?>">
  <table border=0 align=center class=gnav>
	<tr>
	  <td>Логин:</td>
	  <td><INPUT TYPE=TEXT name='user'  size=16 maxlength=16></td>
	</tr>
	<tr>
	  <td>Пароль:</td>
	  <td><INPUT TYPE=password name='pass'  size=16 maxlength=16></td>
	</tr>
	<tr>
	  <td></td>
	  <td><input TYPE=submit name="submit" value="Войти"></td>
	</tr>
  </table>
</form>
<?
	display_bot();
}

function process_form() {
	$uname=$_POST['user'];
	$upass=$_POST['pass'];
	$res = pg_exec("SELECT user_name from users WHERE user_name = '$uname' AND passwd = '$upass'");
	if (pg_numrows($res)<1) {
		display_top();
		display_error(1);
		display_bot();
	}
	else {
		$sid = get_rand_uid();
		$ret = pg_exec("DELETE FROM web_sess WHERE uid=get_id('$uname')");
		$ret = pg_exec("INSERT INTO web_sess (sid,uid) VALUES ('$sid',get_id('$uname'))");
		setcookie("sid",$sid,time()+7*24*60*60*60);
		display_self();
	}
}

function display_top() {
	global $PHP_SELF;
	if (isset($_COOKIE['sid'])) {$sid=$_COOKIE['sid'];} else {$sid="";}
	?>
<html>
<head>
<title>[Статистика]</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<META HTTP-EQUIV="Expires" CONTENT="Mon, 25 Sep 2001 00:02:01 GMT">
<link rel="stylesheet" href="/stat/ht/all.css" type="text/css">
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="bgmain"> 
<style type="text/css">
	.plain {height:20px; vertical-align:middle;}
img.percentImage {
 background: white url(/stat/images/percentImage_back.png) top left no-repeat;
 padding: 0;
 margin: 5px 0 0 0;
 background-position: 1px 0;
}
</style> 
<?if (get_name($sid)) { ?> 
<table width="100%"> 
  <tr> 
	<td align="left"> <table align=left border=0 class=gnav> 
		<tr> 
		  <td><a href=.>Главная</a></td> 
		  <td><a href=<? echo $PHP_SELF;?>?act=stat>Статистика</a></td> 
		  <?if (chk_access()){?> 
		  <td><a href=<? echo $PHP_SELF;?>?act=list>Пользователи</a></td> 
		  <?}?> 
		</tr> 
	  </table></td> 
	<td> <table align=right border=0 class=gnav> 
		<tr> 
		  <td><a href=<? echo $PHP_SELF;?>?act=exit>Выйти из кабинета</a></td> 
		</tr> 
	  </table></td> 
  </tr> 
</table> 
<?}
	print "<br><br>";
}

function display_bot() {
	?> 
<br> 
<br> 
<br> 
<hr> 
<p class=copyright align=right>Copyright by Maxim Cheusov (c)<br> 
</p> 
<iframe width=132 height=142 name="gToday:contrast:agenda.js" id="gToday:contrast:agenda.js" src="DateRange/ipopeng.htm" scrolling="no" frameborder="0" style="visibility:visible; z-index:999; position:absolute; top:-500px; left:-500px;"></iframe> 
</body>
</html>
<?
	exit;
}

function display_error($err) {
	if ($err==1) {
	print "<p class=gnav align=center><font class=err>ОШИБКА!</font> Неверный логин или пароль.</p>";
	}
	if ($err==2) {
	print "<p class=gnav align=center><font class=err>ОШИБКА!</font> Неправельный ПИН-код или номер карты.</p>";
	}
	if ($err==3) {
	print "<p class=gnav align=center><font class=err>ОШИБКА!</font> Неизвестная ошибка системы.</p>";
	}
	if ($err==4) {
	print "<p class=gnav align=center><font class=err>ОШИБКА!</font> Система приема платежей временно отключена.</p>";
	}
	if ($err==5) {
	print "<p class=gnav align=center><font class=err>ОШИБКА!</font> Неверное имя пользователя.</p>";
	}

	display_bot();
	exit;
}

function display_stat($user) {
  global $PHP_SELF;
  display_top();
  display_banner();
  $ret = pg_exec("SELECT users.sum, users.active, tarif.price FROM users, tarif  WHERE users.user_name = '$user' AND tarif.id=users.tarif");
  $num=sprintf("%.2f руб. (%.2f Мб)",pg_result($ret,0,0),pg_result($ret,0,0)/pg_result($ret,0,2));
  $active=pg_result($ret,0,1);
  ?>
<table border=0 align=center class=gnav>
  <tr>
	<td class=err>Пользователь:</td>
	<td><? echo $user; ?></td>
  </tr>
  <tr>
	<td class=err>Остаток:</td>
	<td><? echo $num; ?>  <a href=<? echo $PHP_SELF;?>?act=pay></td>
  </tr>
  <? if ($active=='t') { 
	  print "<tr><td class=err>Статус: </td><td bgcolor=green align=center><font color=white>Активен</font></td></tr>";
  } else {
	  print "<tr><td class=err>Статус: </td><td bgcolor=red align=center><font color=white>Отключен</font></td></tr>";
  }
  ?>
</table>
<br>
<?
  $ret = pg_exec("SELECT tarif.price, tarif.name, tarif.price_local FROM tarif,users WHERE users.user_name='$user' and tarif.id=users.tarif");
  ?>
<TABLE align=center border=0 class=gnav>
  <TR>
	<TD class=err colspan=3 align=center>Тариф</TD>
  </TR>
  <TR>
	<TD class=err>Название</TD>
	<TD class=err>Цена за мб.</TD>
<!--	<TD class=err>Цена (Городской)</TD> -->
  </TR>
  <TR align=center>
	<TD><? echo pg_result($ret,0,1); ?></TD>
	<TD><? echo pg_result($ret,0,0); ?></TD>
	<!-- <TD><? echo pg_result($ret,0,2); ?></TD>  -->
  </TR>
</TABLE>
<?
	display_bot();
}

function display_self() {
	Header("Location: ."); 
	exit;
}

function show_stat($start,$stop,$type,$user,$traf_type) {

	if ($type==1){
		$tp="minutes";
		$reg="(.*)";
	$int="1 day";
	}

	if ($type==2){
		$tp="day";
		$int="1 month";
	}

	if ($type==3){
		$tp="month";
		$int="1 year";
	}

	$mon['01']="Январь";
	$mon['02']="Февраль";
	$mon['03']="Март";
	$mon['04']="Апрель";
	$mon['05']="Май";
	$mon['06']="Июнь";
	$mon['07']="Июль";
	$mon['08']="Август";
	$mon['09']="Сентябрь";
	$mon['10']="Октябрь";
	$mon['11']="Ноябрь";
	$mon['12']="Декабрь";

	$max=0;
						
	$ret = pg_exec("SELECT sum(inp) from statnew where time >='$start' and time <= '$stop' and user_name like '$user%' and statnew.type=$traf_type group by date_trunc('$tp',time) ORDER BY date_trunc('$tp',time) ASC");
	for($i=0;$i<pg_numrows($ret);$i++) {
	if ($max < pg_result($ret,$i,0)) {$max= pg_result($ret,$i,0);}
	}
	$ret = pg_exec("SELECT date_trunc('$tp',time),sum(inp) from statnew where time >='$start' and time <= '$stop' and user_name like '$user%' and statnew.type=$traf_type group by date_trunc('$tp',time) ORDER BY date_trunc('$tp',time) ASC");
	$total=0;
	?>
<table border=0 cellspacing='1' cellpadding='3' class=gnav align=center>
<tr bgcolor=#FFFFBB>
  <td>Дата и время</td>
  <td>Трафик</td>
  <td></td>
</tr>
<?
	$m=1;
	for($i=0;$i<pg_numrows($ret);$i++) {
	$dd=split('[- ]',pg_result($ret,$i,0));
	$month=$dd[1];
	if ($type==1) {$date="$dd[0] $mon[$month] $dd[2] $dd[3]";}
	if ($type==2) {$date="$dd[0] $mon[$month] $dd[2]";}
	if ($type==3) {$date="$dd[0] $mon[$month]";}
	show_bar(122*pg_result($ret,$i,1)/$max,pg_result($ret,$i,1),$date);
	$total+=pg_result($ret,$i,1);
	}
	printf ("<tr bgcolor=#FFFFFF><td colspan=2>Всего за указанный период:</td><td>%.2f Мб</td></tr>",$total/(1000.*1000.));
	print "</table>";
}

function show_stat_users($start,$stop,$traf_type) {
	$max=0;
	$ret = pg_exec("SELECT sum(inp) from statnew where time >='$start' and time <= '$stop' and statnew.type=$traf_type group by user_name");
	for($i=0;$i<pg_numrows($ret);$i++) {
	if ($max < pg_result($ret,$i,0)) {$max= pg_result($ret,$i,0);}
	}
	$ret = pg_exec("SELECT user_name ,sum(inp) from statnew where time >='$start' and time <= '$stop' and statnew.type=$traf_type group by user_name order by sum(inp) desc");
	$total=0;
	?>
<table border=0 cellspacing='1' cellpadding='3' class=gnav align=center>
<tr bgcolor=#FFFFBB>
  <td>Пользователь</td>
  <td>Трафик</td>
  <td></td>
</tr>
<?
	$m=1;
	for($i=0;$i<pg_numrows($ret);$i++) {
	$total+=pg_result($ret,$i,1);
	show_bar(122*pg_result($ret,$i,1)/$max,pg_result($ret,$i,1),pg_result($ret,$i,0));
	}
	printf ("<tr bgcolor=#FFFFFF><td colspan=2>Всего за указанный период:</td><td>%.2f Мб</td></tr>",$total/(1000.*1000.));
	print "</table>";
}

function show_bar($lenght,$value,$description) {
	$value_f=sprintf ("%.2f Мб",$value/1000000);
?>

<tr bgcolor=#FFFFFF>
	<td><?echo $description;?></td>
	<td><?echo $value;?></td>
<!--	<td><img width=<?echo $lenght;?> height=10 src=./ht/statbar.png /> <?echo $value_f;?></td>-->
<td><img src="/stat/images/percentImage.png" alt="<?echo $lenght-122;?>" class="percentImage" style="background-position: <?echo $lenght-122;?>px 0pt;" /></td>
</tr>
<?
}

function display_pay()
{ ?>
<table cellpadding=10 bgcolor=d0d0d0 cellspacing=1 border=0 align=center class=gnav>
  <tr bgcolor=e0e0e0 height=25>
	<td colspan=2 ><big><b>Активация карты оплаты</b></big>
  <tr bgcolor=f0f0f0>
	<td width=70 valign=top bgcolor=f5f5f5>Введите данные
	<td width=250 valign=top><table cellpadding=3 cellspacing=1 border=0 width=100% bgcolor=d0d0d0 class=gnav>
		<form name=card_pay action="<? echo $PHP_SELF;?>" method=post>
		  <input type=hidden name=action value=doAddCardPay>
		  <tr bgcolor=f5f5f5>
			<td class=err><font size=-1>Серийный номер</font>
			<td><input type=text size='10' maxlength='10' id=serialn name="serial">
		  <tr bgcolor=f5f5f5>
			<td><font class=err size=-1>ПИН-код</font>
			<td><input type=text size='15' maxlength='15'  name="pin">
		  <tr bgcolor=f5f5f5>
			<td colspan=2 align=right><input type=submit name=pay value="Активировать">
		</form>
	  </table>
</table>
<?
}

function chk_access() {
	if (get_name($_COOKIE['sid'])=="admin") {return 1;}
	return 0;
}

function display_banner() {
#	print "<p align=center class=gnav>Внимание: Введена тарификация городского трафика. Цена: 0.13р. Подробности на <a href='http://boomba.multi-net.ru/forum/viewtopic.php?p=749#749'><font color=red>форуме</font></a>.</p>";
}

function get_rand_uid() {
	$string = "";
	$stringLength=40; // Длина генерируемой строки
	  for ($index = 1; $index <= $stringLength; $index++) {
		   $randomNumber = rand(1, 62);
		   if ($randomNumber < 11)
				$string .= Chr($randomNumber + 48 - 1);
		   else if ($randomNumber < 37)
				$string .= Chr($randomNumber + 65 - 10);
		   else
				$string .= Chr($randomNumber + 97 - 36);
		   }
	return $string;
}

?>