=== bbP Markdown ===
Contributors: mechter
Donate link: https://www.markusechterhoff.com/donation/
Tags: bbpress, markdown
Requires at least: 3.6
Tested up to: 5.0
Stable tag: 1.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Elegant Markdown support for your bbPress forums.



== Description ==

This is the Markdown plugin for bbPress that I needed, but could not find. It replaces the standard editor with a responsive Markdown form that includes a preview and formatting help. The Markdown is parsed using PHP Markdown(Extra) and saved as a regular HTML bbPress post. The Markdown is saved separately for later editing. The plugin can be activated and deactivated without affecting your bbPress data, it uninstalls cleanly (deleting any saved Markdown code) and is multisite compatible.

Tip: You can give the plugin a slight performance boost if your forum is Markdown only or you don't care that some non-Markdown posts are displayed slightly different than before (e.g. not turning newlines into paragraphs or no longer making URLs automatically clickable), by adding the following to your theme's functions.php: `add_filter( 'bbpmd_remove_output_filters_for_all_posts', '__return_true' );`



== Changelog ==

= 1.5 =

* bbpmd_help_content is now an action rather than a filter
* minor code improvements

= 1.4 =

* added new filter: bbpmd_help_content

= 1.3 =

* brought back the larger margin lost in the previous update

= 1.2 =

* fixed a few bugs

= 1.1 =

* the markdown form can now be loaded multiple times on the same page
* removed a set of standard bbp content output filters that conflicts with the intended markdown output
* added filter to disable bbp content output filter removal
* added filter to selectively disable output filtering for markdown posts only
* changed "Message" to "Write"
* added filters for "Write", "Preview" and "Help"
* added examples for linebreak and paragraph break
* min-height of preview panel is now set dynamically
* cleaned up html output

= 1.0 =

* initial release
