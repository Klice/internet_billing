#!/usr/bin/perl

use DBI;
use Getopt::Std;
use LWP::UserAgent; 
#use LWP::Debug qw(+); 
use HTTP::Cookies;
use Time::localtime;

$log="/var/log/billing/pay.log";
$lock_file="/usr/local/scripts/lock/pay.lck";

getopt("npu");
$card_num=$opt_n;
$card_pin=$opt_p;
$username=$opt_u;
$date=GetTime();
open(SENDMAIL, "|/usr/sbin/sendmail -t") or die "Can't fork for sendmail: $!\n";
print SENDMAIL <<"EOF";
From: Pay System <root\@boomba.multi-net.ru>
To: Administrator <root\@boomba.multi-net.ru>
Subject: Попытка отпраки платежа

Дата: $date
Номер карты: $card_num
ПИН-код: $card_pin
Пользователь: $username

EOF

if (!$username) {cr_err("ОШИБКА: Не указанн логин или имя пользователя");}

my %ac = ( 
	 "login"=>"Santiago",
	 "pwd"=>"EnSeNvMJ",
	 "action"=>"doLogin",
	 "submit"=>"Войти");

my %card = ( 
	 "serial"=>$card_num,
	 "pin"=>$card_pin,
	 "action"=>"doAddCardPay",
	 "submit"=>"Активировать");





my $ua = LWP::UserAgent->new; 
my $cookie_jar = HTTP::Cookies->new(); 
$ua->cookie_jar($cookie_jar);
$ua->post('https://stat.multi-net.ru/_act.jsp',\%ac); 
$response=$ua->post('https://stat.multi-net.ru/_act.jsp',\%card);


$_=$response->content;

($res)=/\<body bgcolor\=ffffff\>\n\<h1\>(.*)\<\/h1\>\<hr\>/;
$_=$res;

print SENDMAIL "Ответ сервера: $res\n";
print SENDMAIL "-----------------------\n";


if (/Ошибка/) {
    print "0";
    $des="Ошибка: неверный ПИН-код или номер карты";
    print SENDMAIL "Решение системы: $des\n";
    $s="USER=$username NUM=$card_num PIN=$card_pin: $des";
    log_add($s);	    
    exit;
}

if (/\d{3,4} рублей принят/) {
    ($sum)=/(\d{3,4})/;
    print "$sum";
    $des="ПЛАТЕЖ: На сумму: $sum принят";
    print SENDMAIL "Решение системы: $des\n";
    $s="USER=$username NUM=$card_num PIN=$card_pin: $des";
    log_add($s);
    add_pay($username,$sum);    
    exit;
}

cr_err("Не верный ответ сервера, блокировка системы");
close(SENDMAIL) or warn "sendmail didn't close nicely";		    

sub newmail() {
    ($resp,$des)=@_;    
    $date=GetTime();    
}

sub GetTime() {
    $tm = localtime;
    $t= sprintf("%02d-%02d-%04d %02d:%02d:%02d",
        $tm->mday, $tm->mon+1, $tm->year+1900, $tm->hour, $tm->min, $tm->sec);
    return $t;    
}

sub log_add {
    my ($string)=@_;
    my $date=GetTime();
    open (LOG, ">> $log");
    print LOG "$date [pay] $string\n";
    close (LOG);
}			

sub add_pay {
    ($name,$sum)=@_;
    print SENDMAIL "Добаление трафика на логин: $name Сумма: $sum\n";
    my $dbh = DBI->connect("dbi:Pg:dbname=vpn", "vpn") || die "Not connected";
    $ret = $dbh->selectall_arrayref("SELECT tarif.price FROM tarif,users WHERE tarif.id=users.tarif and users.user_name='$name'");
    $price=$ret->[0][0];
    $traff=1000000*$sum/$price;
    $traff=sprintf("%.0f",$traff);
    print SENDMAIL "Цена: $price Трафик: $traff\n";
    $dbh->do("SELECT add_money(users.id,$sum) FROM users WHERE users.user_name='$name'");		    
}

sub cr_err() {
    ($des)=@_;
    print "-1";
    print SENDMAIL "ОШИБКА: $des\n";
    print SENDMAIL "Блокировка системы\n";
    log_add("USER=$username NUM=$card_num PIN=$card_pin: $des");
    `touch $lock_file`;
    close(SENDMAIL) or warn "sendmail didn't close nicely";
    exit;
}