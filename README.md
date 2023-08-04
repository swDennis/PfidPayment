# Documentation how to create this app

## Step 1

Create a new Symfony project by the composer with the following command:
> composer create-project symfony/skeleton:"6.2.*" pfidpayment

Replace `pfidPayment` with your project name

## Step 2 
### Now we install our requirements

- Require the Shopware App Bundle:
> composer require shopware/app-bundle

- Require the logger:
> composer require logger

- Require Symfony make
> composer require make

- Require migrations
> composer require migrations

- Because we use the apache2 we have to require the Symfony apacheBundle
> composer require symfony/apache-pack

- Test the integration and call your installation in your browser

---
**INFO**

Make sure if vHost does not target the `public` directory, you have to add `/public`  to all paths.
---

## Step 3 
### install the Shopware CLI
- Download and install the Shopware CLI with
> curl -1sLf \
'https://dl.cloudsmith.io/public/friendsofshopware/stable/setup.deb.sh' \
| sudo -E bash && sudo apt install shopware-cli

- Configure the Shopware CLI with:
> shopware-cli project config init

- Create a new directory for the ShopwareApp. In this case the directory is called `release`

- For an easier use we have created a new file called `update_app.sh` to upload and update the ShopwareApp to Shopware.
File content:
> shopware-cli project extension upload release --activate --increase-version

- Make `update_app.sh` executable:
> sudo chmod +x update_app.sh

## Step 4 
### Adjust the `.env` file 
with the following content:
```dotenv
APP_NAME=PfidPayment
DATABASE_URL="mysql://root:root@mysql:3306/pfid_payment_app"
```
The APP_SECRET is already in the file. Adjust the APP_NAME and DATABASE_URL for your environment.

After the adjustments your `.env` should look like the follow
```dotenv
APP_ENV=dev
APP_SECRET=45adb540d397f0a52c5eb05a2c042b16
APP_NAME=PfidPayment
DATABASE_URL="mysql://root:root@mysql:3306/pfid_payment_app"
```

## Step 5
### Configure the ShopwareAppBundle
- First we have to create a new Entity `src/Entity/Shop.php` wich extends from `Shopware\AppBundle\Entity\AbstractShop`. This is for saving the shop data into the database.
It should look like the following:
```PHP
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shopware\AppBundle\Entity\AbstractShop;

#[ORM\Entity]
class Shop extends AbstractShop
{

}
```

- Create a new file: `config/packages/shopware_app.yaml` with the content:
```yml
shopware_app:
  shop_class: App\Entity\Shop
  name: '%env(APP_NAME)%'
  secret: TestSecret
  #  secret: '%env(APP_SECRET)%'
``` 

- Then create a new file `config/routes/shopware_app.yaml` with the content:

This is currently because the ShopwareAppBundle cannot register the routes.
```yaml
shopware_app_routes_lifecycle:
   resource: '@ShopwareAppBundle/Resources/config/routing/lifecycle.xml'

shopware_app_routes_webhook:
   resource: '@ShopwareAppBundle/Resources/config/routing/webhook.xml'
```

- Then we can register the ShopwareAppBundle in file `config/bundles.php`
```php
<?php

return [
    ...
    \Shopware\AppBundle\ShopwareAppBundle::class => ['all' => true],
    ...
];

```

- Now we can create a migration and migrate the database:
```shell
php bin/console doctrine:database:create &&
php bin/console make:migration &&
php bin/console doctrine:migrations:migrate
```

## Step 6
We can start to create the ShopwareApp in the directory we created for it: In this case it is `release`

- For this, we first create `release/manifest.xml`
- In this case we also create the file `release/Resources/config/plugin.png`

## Step 7
Now we can call the `update_app.sh` to install the ShopwareApp in our Shopware environment with: `./update_app.sh

---
**CHECK**

Check the installation in the shopware admin. The App should be installed and activated.
---

## Step 8
If the installation works, we can a payment method to the `release/manifest.xml`.
```xml
<?xml version="1.0" encoding="UTF-8"?>
<manifest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/platform/trunk/src/Core/Framework/App/Manifest/Schema/manifest-2.0.xsd">
    ...
    <payments>
        <payment-method>
            <identifier>simplePfidPayment</identifier>
            <icon>Resources/Resources/pfidPaymentLogo.png</icon>
            <name>Simple Pfid Payment</name>
            <name lang="de-DE">Einfache Pfid Zahlung</name>
            <description>Pay fast and easy with Pfid Payment.</description>
            <description lang="de-DE">Zahle schnell und einfach mit Pfid Zahlung.</description>
            <validate-url>http://pfidPayment.localhost/public/payment/validate</validate-url>
            <capture-url>http://pfidPayment.localhost/public/capture-payment</capture-url>
        </payment-method>
    </payments>
    ...
</manifest>

```

Then we can add a new controller for the payment method

We start with the `src/Controller/PaymentValidateController.php`.

