# Getting Started With FrameworkUserBundle

## Installation

    composer require sumocoders/framework-user-bundle

Enable the bundle in the kernel.

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    // ...
    $bundles = array(
        // ...
        new FOS\UserBundle\FOSUserBundle(),
        new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
        new SumoCoders\FrameworkUserBundle\SumoCodersFrameworkUserBundle(),
    );
}
```
