=== s3 Contabo Object Storage Cloud File Manager ===
Contributors: sminozzi
Tags: s3cloud, Contabo, s3 cloud, S3, cloud Browse 
Requires at least: 5.2
Tested up to: 6.6
Stable tag: 2.36
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

S3 Contabo Cloud Manager Files: Easily delete, download or upload files or folders between S3 Contabo object storage and your server or computer.
== Description ==
**s3 Cloud Plugin**
★★★★★<br>
>This plugin connect you with your Contabo S3-compatible Object Storage, using S3-compatible API.
>If the file exists on destination, will be overwrited.

Effortless S3 Contabo Management:

This plugin simplifies managing your S3 Contabo object storage. Upload, download, delete, and create folders directly within your S3 storage, eliminating the need for server uploads.

==Flexible File Transfers:==

Move files seamlessly between your server, local computer, and S3 Contabo storage, without occupying server storage. Enjoy current transfer limits of 1GB per file, with larger capacities coming soon!

==Granular Backups with Reduced Restoration Time:==

Perform complete backups of your website to your S3 storage. Unlike traditional backups, you can restore individual files or folders quickly, minimizing the amount of data needed and reducing the time and the risk of overwriting recent changes.

==Free and Easy Site Migrations:==

Move or clone your website with ease, without paying any fees and without downtime between hostings, even if your current hosting provider doesn't offer cPanel/Plesk or you're migrating to a different domain and hosting altogether.

<a href="https://s3cloudplugin.com">Learn More at Plugin Site</a>

== Important ==

Run only one instance of this plugin each time.

== Server Requirements ==

PHP Memory Limit:         256M
WordPress Memory Limit:   256M
Upload Max Size:          1000M
Post Max Size:            1000M
Upload Max Filesize:      1000M
Enable the php-curl module in your PHP setup

If you use nginx, aAdd following code to nginx.conf:

http {
        client_max_body_size 1000M;
}

To know your server  info, install our free plugin WPTOOLS anc contact your host company.


== Multisite ==
This plugin was not tested with Multisite. 



<a href="https://s3cloud.com">Free Plugin for Amazon AWS S3 Object Storage</a>

<a href="https://s3cloudplugin.com/cloning-or-moving-site/">Cloning or Moving Site Without Downtime </a>

<a href="https://s3cloudplugin.com/help/">Online Documentation</a>

<a href="https://s3cloudplugin.com/demo-video//">Demo Video</a>
    
<a href="https://billminozzi.com/dove/">Support Site</a>

<a href="https://siterightaway.net/troubleshooting/">Plugin Troubleshooting</a>

<a href="https://database-backup.com/">Free Plugin to Generate a site database file for Backup</a>
This easy to use free plugin (database-backup) can generate a database backup file with just one click.

<a href="https://bigdumprestore.com/">Free Plugin to Restore a large or very large site database file</a>
This easy to use free plugin (bigdump-restore) can instal the bigdump.php free script to friendly restore your database backup.
 

== Screenshots ==
1. Dashboard 
2. Dashboard Settings Tab
3. Debug Tab 
4. S3 Browse Tab
5. Transfer Folders to/from Server and Cloud




== Installation ==


1) Install via wordpress.org

2) Activate the plugin through the 'Plugins' menu in WordPress

or

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.


== Frequently Asked Questions ==

=Can I do a Complete Backup to Cloud with this plugin?=
Yes, sure. Read this page with complete details:
<a href="https://s3cloudplugin.com/backup-your-site/">Complete Backup Instructions</a>

=Can I move or clone my site without paying any fees and without downtime?=
Yes, sure. Read this page with complete details:
<a href="https://s3cloudplugin.com/cloning-or-moving-site/">Complete Move Instructions</a>


=How to Install?=

1) Install via wordpress.org

2) Activate the plugin through the 'Plugins' menu in WordPress

or

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.


=What is the transfer speed?=

Depends of your computer and server, Server memory, internet connection and the S3 cloud service.

=Got Error 4XX to Upload File from Desktop to Cloud=
If you got "413 Request Entity Too Large - File Upload Issue" or similar, 
usually is a question of server upload tunning. Look the Server Requirements above.
Sometines, large files upload are also blocked by Mod Security on your server.



== Changelog ==
2024-07-18   - Version 2.36 - Small Improvements
2024-07-17   - Version 2.33/35 - Small Improvements
2024-07-12   - Version 2.32 - Small Improvements
2024-07-11   - Version 2.31 - Security Improvement
2024-05-20   - Version 2.25 - Small Improements at readme file
2024-03-26   - Version 2.24 - Small Improements
2024-01-17   - Version 2.23 - Fixed Javascript error "modal..."
2023-12-27   - Version 2.22 - Improved error management.
2023-10-17   - Version 2.21 - Improved error management.
2023-09-05   - Version 2.20 - Improved error management.
2023-08-30   - Version 2.19 - Improved error management.
2023-08-29   - Version 2.17/18 - Improved error management.
2023-03-08   - Version 2.16 - Increased Upload and Download Limit.
2023-02-05   - Version 2.15 - Increased Transfer Limit to 1Gb and small improvements
2023-01-27   - Version 2.11/2.14 - Fixed small bugs
2023-01-21   - Version 2.09/2.10 - Fixed small bugs
2023-01-20   - Version 2.08 - Increased Transfer Limit to 600 Mb
2023-01-13   - Version 2.07 - Increased Transfer Limit
2023-01-12   - Version 2.02/2.06 - Improved Cleaning Temp Files
2023-01-10   - Version 2.01 - Improved Cancell Transfer dialog
2023-01-10   - Version 2.00 - Added Transfer from/to Server and S3 Cloud
2022-September-25 - Version 1.01 - Small Improvements
2022-September-25 - Version 1.00 - Initial release
