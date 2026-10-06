# Image Crop Field for ACF #
Contributors: chrisku, andersthorborg
Tags: acf, advanced custom fields, image crop, image, crop
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An Advanced Custom Fields image field that lets editors crop the selected image to a registered image size or custom dimensions.

## Credits ##

Image Crop Field for ACF is a maintained fork of [ACF Image Crop](https://github.com/andersthorborg/ACF-Image-Crop) by Anders Thorborg, which is no longer updated. The original code is used under the GPLv2-or-later license. The fork is maintained by Christian Kuffner.

## Description ##

Image Crop Field for ACF is an extended version of the native Image-field in ACF.
The field gives the developer/administrator the option to predefine a size for the image, which the user is prompted to crop on the various edit screens. This solves the common issue of images being cropped inappropriately by the automated center-crop, that wordpress performs.

The plugin supports the defined image sizes as well as a custom option, enabling the developer to specify the dimensions from within the field edit screen.

The field can be configured to enforce a hard crop or a minimal-dimension-based crop. The hard crop will lock the aspect ratio of the crop where as the minimal-dimension-based crop will not allow the user to crop the image below the specified dimensions.

This plugin diverts from plugins like [Manual Image Crop](http://wordpress.org/plugins/manual-image-crop/) in that when the user crops an image, a new attachment is generated, so that the relevant crop only applies in the context it is edited. It also keeps the user from dealing with the concept of various image sizes.

As of version 1.0 the field can be configured to either create the cropped image as a media-item (the default behavior) or simply create it and refer directly to the file without adding it to the media library. This will prevent the media library from being cluttered with several cropped versions of the same image. When this option is selected the only available return type for the field is URL.

### Compatibility ###

* Advanced Custom Fields / ACF Pro 6.0 or later (tested against ACF Pro 6.8)
* WordPress 6.2 or later
* PHP 7.4 or later

## Installation ##

1. Copy the plugin folder into `wp-content/plugins`.
2. Activate "Image Crop Field for ACF" on the Plugins screen.

### Upgrading from the original ACF Image Crop add-on ###

This plugin uses the same field type (`image_crop`), settings and stored values as the original add-on, so existing fields and images keep working.

1. Deactivate "Advanced Custom Fields: Image Crop Add-on".
2. Activate "Image Crop Field for ACF".

The two plugins cannot run at the same time. While the original is active, this plugin stays inactive and shows an admin notice.

## Screenshots ##

1. Use a registered image size as the field target size
2. Or use custom dimensions on the fly
3. On the edit screen, select/upload an image as usual
4. A crop-button will appear beneath the image (If desired, use the "Force user crop"-option to initialize the crop as soon as the user selects the image)
5. The image is cropped to the desired format, using the restrictions set under field options
6. The new format is shown using the specified preview size. The original image is kept with the field, so the image can be re-cropped at any time.

## Changelog ##

### 2.0.0 ###
* Forked from ACF Image Crop 1.4.12 and renamed to Image Crop Field for ACF. Field type, settings and stored values are unchanged.
* Security: the crop AJAX actions now require a nonce and the `upload_files` capability, only accept image attachments, and validate the crop coordinates and output size.
* Security: field values are validated when saved, rendered and returned, and the field's image URLs are escaped (stored XSS).
* Fixed a fatal error when the image editor could not open an image.
* Fixed a PHP 8 TypeError with retina mode and a blank custom dimension, and several PHP 8 warnings.
* "Hide cropped images" no longer replaces other plugins' media library filters.
* ACF 6: custom width/height settings are only shown for "Custom size", unused Validation tab settings are hidden, and Preview Size moved to the Presentation tab.
* Selecting several images at once inside a Repeater is no longer offered (it relied on an ACF API that no longer exists).
* Error messages are shown as text, and the field works in front-end forms (`acf_form()`).
* Removed ACF 3 and ACF 4 support. Requires ACF 6.0+, WordPress 6.2+ and PHP 7.4+.

## Original changelog (ACF Image Crop by Anders Thorborg) ##

### 1.4.12 ###
* Fix compatibility with ACF Pro 5.6.0

### 1.4.11 ###
* Address issue with changed ACF Pro validation behavior causing php warnings when saving fields

### 1.4.10 ###
* Add compatibility with ACF Pro 5.5.5

### 1.4.9 ###
* Use acf-image-crop/filename_postfix to allow custom filename postfixes

### 1.4.8 ###
* Fix button styling
* Prevent php warnings for unset field settings

### 1.4.7 ###
* Added compatibility with ACF PRO 5.4.2.2 icons

### 1.4.6 ###
* Added compatibility with ACF 4.4.2 and ACF PRO 5.2.9

### 1.4.5 ###
* Added compatibility with ACF 5.2.7
* Added image quality filter (needs testing)

### 1.4.4 ###
* Fixed migration from image field to not only return image ID
* Fixed a js error in field settings caused by a change in class names in ACF

### 1.4.3 ###
* Removed unused assets

### 1.4.2 ###
* Improved migration from standard field to ACF crop field
* Fixed and issue that caused warnings when options was not set
* Fixed crop preview not showing correct crop position in some browsers
* Improved error handling when server setup does not support image handling
* Fixed original image data missing when saving to media library in v4
* Improved error handling in v4

### 1.4.1 ###
* Fixed issue with image not cropping in v4

### 1.4 ###
* Fixed images with dot in the file name resulting in odd cropped image names
* Fixed issues with php notices in v4
* Fixed issues with broken image fields in v4
* Temporarily fixed images smaller than preview size not being added (ACF bug)
* Updated localization thanks to @tmconnect
* Various tweaks and fixes by @tmconnect

### 1.3 ###
* Updated to be compatible with original image field changes as of ACF Pro 5.0.8. IMPORTANT: As this is a quick fix to ensure compatability with the newest ACF PRO version it is not backwards compatible. If you are using ACF Pro 5.0.7 and below, please use version 1.2 of this add-on.

### 1.2 ###
* Improved: Edit image is now working for most cropped image fields.
* Fix: Wrong GUID for generated images that could cause issues when moving a site to a new location
* Tweak: Added "original_image"-attribute when using return type "Object", containing the original image data.
* Tweak: Return type "Object" is now available when not saving cropped image to media library. The data except from url, width and height is fetched from the original image.
* Feature: It is now possible to hide cropped images from the media dialog. (See the new settings section) NB.: Only works for future cropped images.
* Feature: Retina-mode, that makes the image field require and crop double the dimensions. Results in better integration with plugins like WP Retina 2x
* Feature: Settings-seciton under Settings -> Media. Here you can choose to hide cropped images from the media dialog as well as enable/disable global retina mode.

### 1.1.4 ###
* Fixed an issue causing a php warning when editing custom fields
* Fixed a js-issue causing image-crop-field hiding all subfields when editing repeater-/flexible content-fields

### 1.1.3 ###
* Fixed another issue with save to media option

### 1.1.2 ###
* Fixed issue with force crop option
* Fixed issue with save to media option
* Fixed issue with return type object

### 1.1.1 ###
* Removed unsued references and that caused php warnings
* Added a missing default value that caused a php warning

### 1.1 ###
* Added ACF5 compatibility.
* Please report any compatibility issues. As this has been an urgent feature request I have not had as much time for testing as I would have liked.

### 1.0 ###
* Added option to save the image to media library or refer directly to the created image, not using the media library.
* Added better compatibility with the native image field making it possible to migrate from the regular image field to the crop-image field without losing the images currently attached. (It doesn't work the other way around)

### 0.8 ###
* Fixed an issue resulting in a black image, when image was cropped without moving the crop handles

### 0.7 ###
* Fixed return types other than image id causing fatal error

### 0.6 ###
* Fix for WP installs with non-standard folder structures

### 0.5 ###
* Initial Release.
