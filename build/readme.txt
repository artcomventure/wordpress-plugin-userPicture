=== Image Sizes ===

Contributors:
Donate link:
Tags: Media, Images, Thumbnails
Requires at least:
Tested up to:
Stable tag:
License: MIT
License URI: https://github.com/artcomventure/wordpress-plugin-imageSizes/blob/master/LICENSE

Edit all available image sizes.

== Description ==

Set user's profile picture in WordPress

== Installation ==

1. Upload files to the `/wp-content/plugins/` directory of your WordPress installation.
  * Either [download the latest files](https://github.com/artcomventure/wordpress-plugin-userPicture/archive/master.zip) and extract zip (optionally rename folder)
  * ... or clone repository:
  ```
  $ cd /PATH/TO/WORDPRESS/wp-content/plugins/
  $ git clone https://github.com/artcomventure/wordpress-plugin-userPicture.git
  ```
  If you want a different folder name than `wordpress-plugin-userPicture` extend clone command by ` 'FOLDERNAME'` (replace the word `'FOLDERNAME'` by your chosen one):
  ```
  $ git clone https://github.com/artcomventure/wordpress-plugin-userPicture.git 'FOLDERNAME'
  ```
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. **Enjoy**

== Usage ==

Set and change the user picture in the corresponding user edit form.

If no user picture is selected the default [Gravatar](https://en.gravatar.com/) _stuff_ still works.

== Plugin Updates ==

Although the plugin is not _yet_ listed on https://wordpress.org/plugins/, you can use WordPress' update functionality to keep it in sync with the files from [GitHub](https://github.com/artcomventure/wordpress-plugin-imageSizes).

**Please use for this our [WordPress Repository Updater](https://github.com/artcomventure/wordpress-plugin-repoUpdater)** with the settings:

* Repository URL: https://github.com/artcomventure/wordpress-plugin-userPicture/
* Subfolder (optionally, if you don't want/need the development files in your environment): build

_We test our plugin through its paces, but we advise you to take all safety precautions before the update. Just in case of the unexpected._

== Questions, concerns, needs, suggestions? ==

Don't hesitate! [Issues](https://github.com/artcomventure/wordpress-plugin-userPicture/issues) welcome.

== Changelog ==

= 1.0.1 - 2017-10-09 =
**Fixed**

* PHP error.

= 1.0.0 - 2017-09-25 =
**Added**

* Initial file commit.
