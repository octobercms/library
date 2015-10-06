Config
=======

An extension of illuminate\config

Modules and plugins can have config files in the /config directory. Plugin and module configuration files are registered automatically.

## Accessing configuration strings

````
// Get a configuration string from the CMS module
echo Config::get('cms::options.allowComments');

// Get a configuration string from the october/blog plugin.
echo Config::get('october.blog::options.allowComments');
````

## Overriding configuration strings

System users can override configuration strings without altering the modules' and plugins' files. This is done by adding configuration files to the app/config directory. To override a plugin's configuration:

````
app
  config
    vendorname
      pluginname
        file.php
````
Example: config/october/blog/options.php

To override a module's configuration:

````
app
  config
    modulename
      file.php
````
Example: config/cms/options.php