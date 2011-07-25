=== BHS MARCXML Importer ===
Contributors: anarchivist
Donate link: http://www.brooklynhistory.org/support/fund.html
Tags: import, libraries, marc, marcxml, metadata
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.6

Imports data from MARCXML records and generates WordPress posts.

== Description ==

Imports data XML file containing MARCXML records, or a Zip file containing XML files containing MARCXML records. The data from the records will be imported (currently using a fixed mapping) and inserted into WordPress posts. The plugin's import process is tailored for MARCXML records containing information about archival material, such as those exported from the Archivists' Toolkit. 

This tool was created as part of the project, "Uncovering the Secrets of Brooklyn's 19th Century Past: Creation to Consolidation," funded by the Council on Library and Information Resources, with additional support from The Gladys Krieble Delmas Foundation.

== Installation ==

This plugin requires the [File_MARC PEAR module.](http://pear.php.net/package/File_MARC/) Please install this module before installing the plugin.

== Screenshots ==

1. Initial screen when uploading a Zip file containing a MARCXML records
2. Import options screen

== Changelog ==

= 0.6 =
* Fixes bug relating to category not being set upon import.

= 0.5.1 =
* First stable public release.

= 0.5 =
* Dedupe names.

= 0.4 =
* Modify handling of XML file parsing and directory reading; uses WP_Filesystem calls.
* Workaround for Archivists' Toolkit export - select shortest 520 field for rendering.
* Added handling to set post metadata options at import time.

= 0.2.1 = 
* Added more fields covered by extraction code.

= 0.2 =
* Initial public release, including ability to extract Zip files.

== Upgrade Notice ==

= None =
* No upgrade notices.

== Frequently Asked Questions ==

= I'm seeing errors that contain "Failed opening required 'File/MARCXML.php'". =

You haven't installed the  [File_MARC PEAR module.](http://pear.php.net/package/File_MARC/) Please install this module before installing the plugin.