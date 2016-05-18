#!/usr/bin/perl

use Archive::Tar;
use Getopt::Std;

my %sum;

@exec=`cat /usr/local/billing/exec.acl`;

getopts("pou:f:");
$looser=$opt_u;
$out_file=$opt_f;
$print_port=$opt_p;
$only_port=$opt_o;


if ($looser eq "") {die "ERROR: No IP address specifed.\n";}
if ($out_file eq "") {die "ERROR: No FILE specifed.\n";}

@list = glob("./tmp/stat*.tar.gz");

foreach (@list) {
    print "Extracting file: $_\n";
    open F, "gunzip -c $_ |" || die $!;
    $tar = Archive::Tar->new(*F);
    @items = $tar->get_files;
    foreach (@items) {
	if ($_->size > 1) {
	    Stat_Proccess(split(/\n/, $tar->get_content( $_->name )));
	}
    }
    close F;
	    
}


open (OUT,"> $out_file");
print OUT "<table>\n";
while (($IP,$SUM)=each(%sum))
{
    print OUT "<tr><td>$IP</td><td>$SUM</td></tr>\n";
}
print OUT "</table>\n";

close (OUT);


sub Stat_Proccess() {
    my @stat=@_;
    foreach (@stat) {
	($ip)=/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/;
	($port)=/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\t(\d{1,4})/;
	($to)=/\t(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/;
	$stat_name=$ip;
	if ($print_port) {$stat_name.="<td>$port</td>";}
	if ($only_port) {$stat_name="$port";}
	$fl=0;
	foreach (@exec){
	    chomp ($_);
	    if (ChkFree($ip,$_)) {$fl=1;}
	}
	if (($fl==0)&&($to eq $looser)) {
    	    ($num)=/(\d*)\t\d*$/;
    	    $sum{$stat_name}+=$num;
        }
    }
}

sub ChkFree {
    my ($find_net,$some_ip)=@_;
    my ($net_ip, $net_mask) = split(/\//, $find_net);
    my ($ip1, $ip2, $ip3, $ip4) = split(/\./, $net_ip);
    my $net_ip_raw = pack ('CCCC', $ip1, $ip2, $ip3, $ip4);
    my $net_mask_raw = pack ('B32', (1 x $net_mask), (1 x (32 - $net_mask)));

    ($ip1, $ip2, $ip3, $ip4) = split(/\./, $some_ip);
    my $some_ip_raw = pack ('CCCC', $ip1, $ip2, $ip3, $ip4);
    if (($some_ip_raw & $net_mask_raw) eq $net_ip_raw){
        return 1;
    }
    return 0;
}
