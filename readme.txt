=== BHS MARCXML Importer ===
Contributors: anarchivist
Tags: import, libraries, marc, marcxml, metadata
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 0.5

Imports data from MARCXML records and generates WordPress posts.

== Description ==

Imports data XML file containing MARCXML records, or a Zip file containing XML files containing MARCXML records. The data from the records will be imported (currently using a fixed mapping) and inserted into WordPress posts. The plugin's import process is tailored for MARCXML records containing information about archival material, such as those exported from the Archivists' Toolkit. 

This tool was created as part of the project, "Uncovering the Secrets of Brooklyn's 19th Century Past: Creation to Consolidation," funded by the Council on Library and Information Resources, with additional support from The Gladys Krieble Delmas Foundation.

== Installation ==

This plugin requires the [File_MARC PEAR module.](http://pear.php.net/package/File_MARC/) Please install this module before installing the plugin.

== Screenshots ==

1. Original uploader (good if you don't want to attach images to another post)
2. Zip uploader media button
3. Second uploader

== Changelog ==

= 0.5 =
* Dedupe names.

= 0.4 =
* Modify handling of XML file parsing and directory reading; uses WP_Filesystem calls.
* Workaround for Archivists' Toolkit export - select shortest 520 field for rendering.
* Added handling to set post metadata options at import time.

= 0.2.1 = 
* Added increased field extraction.

= 0.2 =
* Initial public release, including ability to extract Zip files.