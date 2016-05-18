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
Subject: ������� ������� �������

����: $date
����� �����: $card_num
���-���: $card_pin
������������: $username

EOF

if (!$username) {cr_err("������: �� ������� ����� ��� ��� ������������");}

my %ac = ( 
	 "login"=>"Santiago",
	 "pwd"=>"EnSeNvMJ",
	 "action"=>"doLogin",
	 "submit"=>"�����");

my %card = ( 
	 "serial"=>$card_num,
	 "pin"=>$card_pin,
	 "action"=>"doAddCardPay",
	 "submit"=>"������������");





my $ua = LWP::UserAgent->new; 
my $cookie_jar = HTTP::Cookies->new(); 
$ua->cookie_jar($cookie_jar);
$ua->post('https://stat.multi-net.ru/_act.jsp',\%ac); 
$response=$ua->post('https://stat.multi-net.ru/_act.jsp',\%card);


$_=$response->content;

($res)=/\<body bgcolor\=ffffff\>\n\<h1\>(.*)\<\/h1\>\<hr\>/;
$_=$res;

print SENDMAIL "����� �������: $res\n";
print SENDMAIL "-----------------------\n";


if (/������/) {
    print "0";
    $des="������: �������� ���-��� ��� ����� �����";
    print SENDMAIL "������� �������: $des\n";
    $s="USER=$username NUM=$card_num PIN=$card_pin: $des";
    log_add($s);	    
    exit;
}

if (/\d{3,4} ������ ������/) {
    ($sum)=/(\d{3,4})/;
    print "$sum";
    $des="������: �� �����: $sum ������";
    print SENDMAIL "������� �������: $des\n";
    $s="USER=$username NUM=$card_num PIN=$card_pin: $des";
    log_add($s);
    add_pay($username,$sum);    
    exit;
}

cr_err("�� ������ ����� �������, ���������� �������");
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
    print SENDMAIL "��������� ������� �� �����: $name �����: $sum\n";
    my $dbh = DBI->connect("dbi:Pg:dbname=vpn", "vpn") || die "Not connected";
    $ret = $dbh->selectall_arrayref("SELECT tarif.price FROM tarif,users WHERE tarif.id=users.tarif and users.user_name='$name'");
    $price=$ret->[0][0];
    $traff=1000000*$sum/$price;
    $traff=sprintf("%.0f",$traff);
    print SENDMAIL "����: $price ������: $traff\n";
    $dbh->do("SELECT add_money(users.id,$sum) FROM users WHERE users.user_name='$name'");		    
}

sub cr_err() {
    ($des)=@_;
    print "-1";
    print SENDMAIL "������: $des\n";
    print SENDMAIL "���������� �������\n";
    log_add("USER=$username NUM=$card_num PIN=$card_pin: $des");
    `touch $lock_file`;
    close(SENDMAIL) or warn "sendmail didn't close nicely";
    exit;
}