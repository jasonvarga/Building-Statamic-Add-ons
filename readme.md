# Building Statamic Add-ons

> Peers Conference - Tuesday 22nd April 2014

## Getting Ready

1. Grab a copy of Statamic 1.7.5 and install it to your local machine. Get it running on a local environment like MAMP.
2. Delete the `denali` and `acadia` theme folders.
3. Copy across `_themes/peers` to the `_themes` folder.
4. Edit your `settings.yaml` to point to your `peers` theme.
5. Overwrite `_app/core/extend/addon.php` with `_resources/addon.php`. This fixes a bug in 1.7.5
6. Copy `resources/bluebird.yaml` to `_config/add-ons/bluebird.yaml`

**Don't** copy across the add-on folders. These are just for your reference. You want to make them yourselves, don't you?
