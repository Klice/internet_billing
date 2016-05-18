#!/usr/bin/perl
use DBI;
use CGI qw(:standard);
#use Net::Ping;

#$p = Net::Ping->new();    
#$p->bind("192.168.100.102");

my $dbh = DBI->connect("dbi:Pg:dbname=vpn", "vpn") || die "Not connected";
$action = param("action");
$user = param("user");
$pass = param("pass");
$ret = $dbh->selectall_arrayref("SELECT user_name, active from users WHERE user_name = '$user' AND passwd = '$pass'");

print "Content-type: text/html; charset=windows-1251\n\n";

open (HEAD, "../ht/head.html");
open (TAIL, "../ht/tail.html");
print <HEAD>;

if (@$ret==1)
{
    $active=$ret->[0][1];
    $ret = $dbh->selectall_arrayref("SELECT param from users_attribute  WHERE user_name = '$user' AND attr = 'Traffic-Limit'");
    $num=sprintf("%.2fMb",$ret->[0][0]/(1000*1000));
    print "<table border=0 align=center class=gnav>";
    print "<tr><td class=err>Пользователь: </td><td>$user</td></tr>";
    print "<tr><td class=err>Остаток: </td><td>$num</td></tr>";
    if ($active) {
	print "<tr><td class=err>Статус: </td><td bgcolor=green align=center><font color=white>Активен</font></td></tr>";
    } else {
	print "<tr><td class=err>Статус: </td><td bgcolor=red align=center><font color=white>Отключен</font></td></tr>";
    }
    print "</table><br>";

    @row = $dbh->selectrow_array(
            "SELECT tarif.price, tarif.name where users.user_name='$user' and tarif.id=users.tarif");
    print ("
	<TABLE align=center border=0 class=gnav>
	<TR>
	    <TD class=err>Тариф</TD>
	    <TD></TD>
	</TR>		    

	<TR>
	    <TD class=err>Название</TD>
	    <TD class=err>Цена</TD>
	</TR>		    
	<TR>
	    <TD>$row[1]</TD>
	    <TD>$row[0]</TD>
	</TR>
	</TABLE>");
					
											
	    
#    if ($p->ping("62.213.32.130")) {
#	print "<tr><td class=err>Интернет: </td><td bgcolor=green><font color=white>Работает</font></td></tr>";
#    } else {
#	print "<tr><td class=err>Интернет: </td><td bgcolor=red><font color=white>Отключен</font></td></tr>";
#    }
#
#print "</table>";
#print "<p align=center><font class=gnav>Статистика за сегоня:</font></p>";

#($start,$year,$mon,$day) =  &GetCurDate;
#$start_v = $start." 00:00:00";
#$stop_v = $start." 23:59:59";



#$ret = $dbh->selectall_arrayref("SELECT start,user_name,tm,inp,out from stat where stop IS NOT NULL AND start >= '$start_v' AND start <= '$stop_v' AND user_name='$user' ORDER BY start ASC");
#print "<TABLE BORDER=0 class='gnav' align=center><TR class='err'><th>Время</th><th>Время</th><th>Отправлено</th><th>Получено</th></tr>";
#$s_i=$s_o=$s_t=0;
#for($i=0;$i<@$ret;$i++) {
#    $_=$ret->[$i][0];
#    ($d)=/(..:..:..)/;
#    print "<TR><TD>$d</TD><TD>$ret->[$i][2]</TD><TD>$ret->[$i][3]</TD><TD>$ret->[$i][4]</TD>    </TR>";
#    $s_t+=$ret->[$i][2];
#    $s_i+=$ret->[$i][3];
#    $s_o+=$ret->[$i][4];
#}
#$s_t=sprintf("%.2fH",$s_t/(60*60));
#$s_o=sprintf("%.2fMb",$s_o/(1024*1024));
#$s_i=sprintf("%.2fMb",$s_i/(1024*1024));
		
#print "<TR class='err'><TD></TD><TH>$s_t</TH><TH>$s_i</TH><TH>$s_o</TH></TR>";
#print "</TABLE>";

#print "<p align=center><font class=gnav>Статистика за месяц:</font></p>";

#($start,$year,$mon,$day) =  &GetCurDate;
#print "<TABLE BORDER=0 class='gnav' align=center><TR class='err'><th>Время</th><th>Время</th><th>Отправлено</th><th>Получено</th></tr>";
#$s_i=$s_o=$s_t=0;

#for ($j=1;$j<=$day;$j++) {

#    $_=$j;
#    $_="0$j" unless /../;
#    $start_v = "$year-$mon-$j 00:00:00";
#    $stop_v = "$year-$mon-$j 23:59:59";

#    $ret = $dbh->selectall_arrayref("SELECT sum(tm),sum(inp),sum(out) from stat where stop IS NOT NULL AND start >= '$start_v' AND start <= '$stop_v' AND user_name='$user' GROUP BY user_name");
#    for($i=0;$i<@$ret;$i++) {
#        print "<TR><TD>$year-$mon-$_</TD><TD>$ret->[$i][0]</TD><TD>$ret->[$i][1]</TD><TD>$ret->[$i][2]</TD>    </TR>";
#        $s_t+=$ret->[$i][0];
#        $s_i+=$ret->[$i][1];
#        $s_o+=$ret->[$i][2];
#    }
#}
#$s_t=sprintf("%.2fH",$s_t/(60*60));
#$s_o=sprintf("%.2fMb",$s_o/(1024*1024));
#$s_i=sprintf("%.2fMb",$s_i/(1024*1024));
		
#print "<TR class='err'><TD></TD><TH>$s_t</TH><TH>$s_i</TH><TH>$s_o</TH></TR>";
#print "</TABLE>";
		
			    

} else 
{
    print "<p class=gnav align=center><font class=err>ОШИБКА!</font> Неправельный логин или пароль.</p>";
}
print <TAIL>;
#$p->close();
close (HEAD);
close (TAIL);


sub GetCurDate {
    local($sec, $min, $hour, $mday, $mon, $year) = localtime;
    $year += 1900;
    $mon++;
    foreach $e ('sec', 'min', 'hour', 'mday', 'mon', 'year') {
        eval "\$$e = 0 . \$$e" if (eval "\$$e" < 10);
    }
    return ("$year-$mon-$mday",$year,$mon,$mday);
}
				
