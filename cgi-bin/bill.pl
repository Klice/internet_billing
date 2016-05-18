#!/usr/bin/perl

use LWP::UserAgent; 
#use LWP::Debug qw(+); 
use HTTP::Cookies;

my %ac = ( 
	 "login"=>"Santiago",
	 "password"=>"EnSeNvMJ",
	 "sendform"=>"//index.php",
	 "submit"=>"Вход");

my $ua = LWP::UserAgent->new;
my $cookie_jar = HTTP::Cookies->new; 
$ua->cookie_jar($cookie_jar);
$ua->post('https://stat.multi-net.ru:9443/login.php?sendform=/index.php',\%ac); 
$response=$ua->get('https://stat.multi-net.ru:9443/index.php');
$ua->get('https://stat.multi-net.ru:9443/exit.php');

$_=$response->content;
($res)=/Текущий остаток: (\d{1,4}\.\d{1,2}) руб.<br>/;
		
$_=$res;
print;