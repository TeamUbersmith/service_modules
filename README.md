# Ubersmith Service Modules

## About Service Modules

Service Modules are extensions to the base Ubersmith functionality that enable advanced billing features. Service Modules can allow for usage based billing using data from external sources, such as the Ubersmith Appliance, or a remote API. Several Service Modules are included with Ubersmith, but it's also possible to build new, custom modules.

## Service Module Development

### Development Environment

It's easiest and safest to perform your development of a new module in a dedicated environment just for development. Please contact Ubersmith Sales to inquire about discounted licensing for development environments.

### Code Location

Custom code can be added to a running Ubersmith installation by placing it in the

```
app/custom
```

folder in the Ubersmith installation directory. For the purposes of this README, we'll assume a default installation directory of `/usr/local/ubersmith`, making the full path:

```
/usr/local/ubersmith/app/custom
```

and the full path for your module's class file  (ex. "`my_module`"):

```
/usr/local/ubersmith/app/custom/include/service_modules/class.my_module.php
```

For more on the paths used in an installation of Ubersmith, see the [Ubersmith and Docker](https://docs.ubersmith.com/article/ubersmith-and-docker-43.html) documentation.

Once your code is in place, restarting the `web` container will load the code into the running installation. To restart the `web` container, run:

```
cd /usr/local/ubersmith
docker-compose restart web
```

Note that the `docker-compose` binary may be located in `$HOME/.local/bin`.

Once your custom code has been loaded into the running Ubersmith installation, you can make changes using your preferred editor by editing the files in `app/custom`. If you prefer to use a graphical IDE, you may want to see if it supports a [remote SSH feature](https://code.visualstudio.com/docs/remote/ssh) to allow you to edit the code in a more familiar environment.

### XDebug

Having PHP's `xdebug` module available during development can be helpful. Ubersmith's `php` container, which is responsible for running PHP code, does have the `xdebug` extension installed, but it is disabled by default. If you'd like to enable it, run:

```
cd /usr/local/ubersmith
docker-compose exec -u root php phpenmod -s ALL xdebug
docker-compose restart php
```

You can verify if the `xdebug` module was loaded successfully by running:

```
docker-compose exec php php -v
```

and looking for the `with Xdebug...` line in the PHP version output.

### Composer

Ubersmith's `php` container includes the `composer` binary, should you need to use it. To run composer, get a shell in Ubersmith's `php` container:

```
cd /usr/local/ubersmith
docker-compose exec php bash
```

This will open a shell within the container. Once inside the running container, change to the directory where your `composer` related files are located and execute `composer install` (or another composer function):

```
cd /var/www/ubersmith_root/app/www/include/service_module/my_module_dependencies
/usr/local/bin/composer install
```

**Warning**  
You will need to re-run `composer install` to bring in any custom dependencies every time Ubersmith is upgraded.

### Autoloader

Ubersmith's Service Module autoloader expects module definitions to be in the respective folder -- in this case, `include/service_modules`. Modules in subfolders will not be found and will not be loaded by the system.

## Example Service Module

Our official service module example lives in the `sm_sample` directory. Please consult this if you're implementing your own service module for Ubersmith.

## Community Contribution

Service modules contributed by the community live in their own subdirectories. If you wish to contribute one, please develop your service module within its own subdirectory, then submit a pull request. Please note that we take no responsibility for service modules contributed by the community, and make no claims as to their functionality or usability. SHOULD THE LIBRARY PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY SERVICING, REPAIR OR CORRECTION.
