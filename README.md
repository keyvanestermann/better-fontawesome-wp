# better-fontawesome

This is a Wordpress plugin. 

It allows you to choose which version of FontAwesome to use with Wordpress and Elementor (currently 5.13.3 or 6.7.2)

See readme.txt for more details


## Screenshots

![options page screenshot](https://raw.githubusercontent.com/keyvanestermann/better-fontawesome-wp/refs/heads/main/assets/screenshot-1.png "Options page")
![elementor builder screenshot](https://raw.githubusercontent.com/keyvanestermann/better-fontawesome-wp/refs/heads/main/assets/screenshot-2.png "Elementor builder")

## Downloading the plugin from this repository

This repository is named "better-fontawesome-wp" for clarity, but you should rename the directory to "better-fontawesome" for it to work

## Known issues

### Cannot render icons as SVG

You need to disable "Inline Font Icons" from the Elementor settings (under Features), because Elementor will try to retrieve the SVG data from JSON files located in its own directory. 