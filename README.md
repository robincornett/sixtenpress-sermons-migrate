# Six/Ten Press Sermons Migration

This is a small plugin to migrate posts from Sermon Manager to Six/Ten Press Sermons.

**Note:** this plugin is in beta and should be used with caution. Make sure your database is backed up and that you've exported your existing sermons before running any migration or copy.

## Requirements
* WordPress 4.4, tested up to 4.7
* Sermon Manager
* Six/Ten Press Sermons
* [Six/Ten Press](https://robincornett.com/downloads/sixtenpress/)

## Installation

### Upload

1. Download the latest tagged archive (choose the "zip" option).
2. Go to the __Plugins -> Add New__ screen and click the __Upload__ tab.
3. Upload the zipped archive directly.
4. Go to the Plugins screen and click __Activate__.
5. Visit the Sermons > Settings page to change the default behavior of the plugin.

### Manual

1. Download the latest tagged archive (choose the "zip" option).
2. Unzip the archive.
3. Copy the folder to your `/wp-content/plugins/` directory.
4. Go to the Plugins screen and click __Activate__.
5. Visit the Sermons > Settings page to change the default behavior of the plugin.

Check out the Codex for more information about [installing plugins manually](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

## Frequently Asked Questions

### How do I get started?
    
Once you've activated this plugin, a notice will be added to your WordPress Admin with instructions. You can test the migration process on just one sermon post, or, if you know everything is working correctly, you can migrate all your sermons in batches of 40.

### Warnings and Notices

This process is one way and irreversible. Your database will be modified. It is your responsibility to back up your database and website. Some notes on the process:

* I **strongly** encourage you to export your Sermon Manager posts (in addition to backing up your site) before beginning the migration process. If the migration fails, you can import them back in.
* Both Six/Ten Press Sermons and Sermon Manager must be running during the migration process.
* Six/Ten Press taxonomies are not all enabled by default, so I recommend enabling them all before running the migration process. You can disable unneeded taxonomies later.
* Sermon Manager's Service Types are equivalent to Six/Ten Press Sermons' Occasions.
* If you've been embedding videos in Sermon Manager, they'll be appended to your sermon description/notes and converted to the post content in Six/Ten Press. They will not automatically be added to the 6/10 Press video field.

## Credits

* Built by [Robin Cornett](https://robincornett.com/)

## Changelog

### 0.1.0
* initial release
