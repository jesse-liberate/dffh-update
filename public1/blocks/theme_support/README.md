Moodle Block Template
=====================

This is a template for Moodle blocks.

It is used by Moosh (http://moosh-online.com/) to generate new block plugins.

* This template assumes that the block is using a textual content type by default. If you want your block to display a list of items (using $this->content->items and $this->content->icons instead of $this->content->text), change the derived class of the block, from extends block_base to extends block_list. For more information: https://docs.moodle.org/dev/Blocks#Additional_Content_Types.

* Go to Settings > Site Administration > Development > XMLDB editor and modify the module's tables.

* Modify version.php and set the initial version of you module.

* Visit Settings > Site Administration > Notifications, you should find
the module's tables successfully created

* Go to Site Administration > Plugins > Blocks > Manage blocks
and you should find that this newblock has been added to the list of
installed modules.

* You may now proceed to run your own code in an attempt to develop
your module. You will probably want to modify block_theme_support.php
and edit_form.php as a first step. Check db/access.php to add
capabilities.

We encourage you to share your code and experience - visit http://moodle.org

Good luck!

[![Build Status](https://travis-ci.org/danielneis/moodle-block_theme_support.svg?branch=master)](https://travis-ci.org/danielneis/moodle-block_theme_support)


## Rename this block

1. Rename the "theme_support" folder to "newname" (use lowercases).
2. Rename the block_theme_support.php to block_newname.php
3. Rename  lang/en/block_theme_support.php  to  lang/en/block_newname.php. Check $string['pluginname'] is correct
4. Replace all strings with the following:
	- block_theme_support	block_newname
	- block/theme_support	block/newname
	- blocks/theme_support	blocks/newname
	- Block theme_support	Block Newname
	- 'theme_support'	'newname'