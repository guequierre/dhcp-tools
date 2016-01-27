# DHCP-Tools

Some tools to monitor isc dhcp-server

### Summary

|file                   | description                                                 |
|-----------------------|-------------------------------------------------------------|
|dhcp-icinga            | check for icinga / nagios.                                  |
|dhcp-influx            | store the dhcp statistics in influxdb for use with grafana. |
|dhcp-munin             | output the statistics to munin.                             |
|dhcp-parser.php        | parser of the dhcp.conf / dhcp.leases files.                |
|dhcp-tools.conf.sample | sample configfile, should be put in /etc                    |

### Usage
#### dhcp-icinga
Just use it as a regular check for icinga, or nagios. It will report the shared-networks wich are over the warning, or error threshold as configured in the configfile.

#### dhcp-influx
It will parse the dhcp files and outputs the statistics to the influx server which is configured in the configfile.
It can be run from a cronjob. 

#### dhcp-munin
Create a symlink to dhcp-munin in /etc/munin/plugins and restart munin-node.

#### dhcp-tools.conf
The tools will look for /etc/dhcp-tools.conf, if not found it will use the default settings. 
