# Semantic Text Analysis with Aylien

## Installation

- Register at [aylien.com](http://aylien.com/) where you get an *App ID* and *App Key*
- Create a file ``config/config.php`` where you add the following lines:

```
    <?php
        define("APP_ID", "YOUR_ALIEN_APP_ID");
        define("APP_KEY", "YOUR_ALIEN_APP_KEY");
    ?>
```

- On Windows run ``php composer.phar install``, on Mac OSX ``composer install`` to install Aylien from the composer repository
- Call ``index.php`` 