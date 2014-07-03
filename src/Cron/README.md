Cron
=======

A simple database driven Queue driver for Laravel.

## Set up

Add the following to your Crontab (`crontab -e`):

```php
* * * * * php /path/to/artisan queue:cron 1>> /dev/null 2>&1
```

> **Note**: If you are adding this to `/etc/cron.d` you'll need to specify a user immediately after `* * * * *`.

## Usage outside OctoberCMS

Add the package to the require section of your composer.json and run `composer update`

    "october/cron": "dev-master"

Then add the Service Provider to the providers array in *config/app.php*:

    'October\Rain\Cron\CronServiceProvider',

You should run the migrations for this package:

    php artisan migrate --package="october/cron"

Also you should add the cron driver to the file **config/queue.php**:

    'default' => 'cron',

    'connections' => array(

        'cron' => array(
            'driver' => 'cron',
        ),

        [...]
    }
