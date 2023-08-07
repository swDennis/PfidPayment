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
If the installation works, we can add a payment method to the `release/manifest.xml`.
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
            <pay-url>http://pfidPayment.localhost/public/payment/pay</pay-url>
            <finalize-url>http://pfidPayment.localhost/public/payment/finalize</finalize-url>
        </payment-method>
    </payments>
    ...
</manifest>

```
After updating the ShopwareApp with the command `./update_app.sh` the new payment-method should be available in Shopware-admin.

- Check if the payment is active: Settings > Payment methods. 
- Add the new payment-method inside your sales channel: Payment and shipping > Payment methods


## Step 9
Create a new PaymentPayController with a route for `/payment/pay`.
```php
<?php
namespace App\Controller;

class PaymentPayController extends AbstractController
{
    public function __construct(private readonly ShopRepositoryInterface $shopRepository, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/payment/pay', name: 'app_payment_pay')]
    public function index(RequestInterface $request): ResponseInterface
    {
        
    }
}
```
This controller shall return a response with a redirect-url:
```php
#[Route('/payment/pay', name: 'app_payment_pay')]
public function index(RequestInterface $request): ResponseInterface
{
    $shopResolver = new ShopResolver($this->shopRepository);
    $signer = new ResponseSigner();
    $contextResolver = new ContextResolver();
    $shop = $shopResolver->resolveShop($request);
    $payment = $contextResolver->assemblePaymentPay($request, $shop);

    $orderPayment = $this->entityManager->getRepository(OrderPayment::class)->saveOrderPayment(
        $payment->order->getId(),
        $payment->returnUrl,
        0
    );

    $url = 'http://pfidpayment.localhost' .$this->generateUrl('app_payment_accept_or_cancel', ['opi' => $orderPayment->getOrderId()]);

    return $signer->signResponse(PaymentResponse::redirect($url), $shop);
}
```
## Step 10
Now we need a user interface where the customer can choose to pay or cancel.
For this create a new Controller which renders a twig-template.
```php
<?php

namespace App\Controller;

#[AsController]
class PayOrNotController extends AbstractController
{
    #[Route('/accepted/or/cancel', name: 'app_payment_accept_or_cancel')]
    public function payOrNot(Request $request): Response
    {
        return $this->render('base.html.twig', [
            'opi' => $request->get('opi')
        ]);
    }
}
```

```html
<!DOCTYPE html>
<html>
    <head>
        ...
    </head>
    <body>
        {% block body %}
            <div>
                <form action="{{ url('app_payment_accepted') }}">
                    <input type="hidden" name="opi" value="{{ opi }}">
                    <input type="submit" value="PAY">
                </form>

                <form action="{{ url('app_payment_cancel') }}">
                    <input type="hidden"  name="opi" value="{{ opi }}">
                    <input type="submit" value="Cancel">
                </form>
            </div>
        {% endblock %}
    </body>
</html>
```
Now we need 2 routes in the `src/Controller/PayOrNotController.php` which handle the forms:
```php
#[Route('/accepted', name: 'app_payment_accepted')]
public function accepted(Request $request, EntityManagerInterface $entityManager): Response
{
    /** @var OrderPayment $orderPayment */
    $orderPayment = $entityManager->getRepository(OrderPayment::class)->findByOrderId($request->get('opi'));

    if (!$orderPayment instanceof OrderPayment) {
        throw new Exception('No $orderPayment found');
    }

    $orderPayment->setAccepted(1);

    $entityManager->persist($orderPayment);
    $entityManager->flush();

    return $this->redirect($orderPayment->getReturnUrl());
}

#[Route('/canceled', name: 'app_payment_cancel')]
public function cancel(Request $request, EntityManagerInterface $entityManager): Response
{
    /** @var OrderPayment $orderPayment */
    $orderPayment = $entityManager->getRepository(OrderPayment::class)->findByOrderId($request->get('opi'));

    if (!$orderPayment instanceof OrderPayment) {
        throw new Exception('No $orderPayment found');
    }

    $orderPayment->setAccepted(0);

    $entityManager->persist($orderPayment);
    $entityManager->flush();

    return $this->redirect($orderPayment->getReturnUrl());
}
```
As you can see we create a new Entity to save the payment state: `src/Entity/OrderPayment.php`:
```php
<?php

namespace App\Entity;

...
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderPaymentRepository::class)]
class OrderPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $orderId = null;

    #[ORM\Column(type: Types::TEXT)]
    private $returnUrl = null;

    #[ORM\Column]
    private ?int $accepted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function setReturnUrl($returnUrl): static
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function getAccepted(): ?int
    {
        return $this->accepted;
    }

    public function setAccepted(int $accepted): static
    {
        $this->accepted = $accepted;

        return $this;
    }
}

```
For the Entity we create this repository `src/Repository/OrderPaymentRepository.php`:
```php
<?php

namespace App\Repository;

/**
 * @extends ServiceEntityRepository<OrderPayment>
 *
 * @method OrderPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderPayment[]    findAll()
 * @method OrderPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderPayment::class);
    }

    public function findByOrderId(string $orderId): ?OrderPayment
    {
        $orderPayment = $this->createQueryBuilder('op')
            ->select(['order_payment'])
            ->from(OrderPayment::class, 'order_payment')
            ->where('order_payment.orderId = :paymentId')
            ->setParameter('paymentId', $orderId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$orderPayment instanceof OrderPayment) {
            return null;
        }

        return $orderPayment;
    }

    public function saveOrderPayment(string $orderId, string $returnUrl, int $accepted): OrderPayment
    {
        $orderPayment = $this->findByOrderId($orderId);
        if (!$orderPayment instanceof OrderPayment) {
            $orderPayment = new OrderPayment();
        }

        $orderPayment->setOrderId($orderId);
        $orderPayment->setReturnUrl($returnUrl);
        $orderPayment->setAccepted($accepted);

        $this->_em->persist($orderPayment);
        $this->_em->flush();

        return $orderPayment;
    }
}

```

At the time we tried this out there was a bug inside the app-bundle: `vendor/shopware/app-bundle/src/ArgumentValueResolver/ContextArgumentResolver.php`
We fix this with the following lines inside the `resolve` method:
```php
public function resolve(Request $request, ArgumentMetadata $argument): iterable
{
    if(!$this->supports($request, $argument)) {
        return;
    }
    ...
}
```
The fix will be updated as soon as possible. If it is not present you can add the code yourself.

## Step 11
Shopware now needs to know if the payment was successful or not. For this the `finalize-url` is called which returns the state:
```php
<?php

namespace App\Controller;

class PaymentFinalizeController extends AbstractController
{
    public function __construct(private readonly ShopRepositoryInterface $shopRepository, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/payment/finalize', name: 'app_payment_finalize')]
    public function index(RequestInterface $request): ResponseInterface
    {
        $orderId = json_decode($request->getBody()->getContents(), true)['orderTransaction']['orderId'];
        $request->getBody()->rewind();

        $shopResolver = new ShopResolver($this->shopRepository);
        $shop = $shopResolver->resolveShop($request);

        $orderPayment = $this->entityManager->getRepository(OrderPayment::class)->findByOrderId($orderId);
        $signer = new ResponseSigner();
        if (!$orderPayment instanceof OrderPayment) {
            return $signer->signResponse(PaymentResponse::failed('Cannot found order'), $shop);
        }

        if ($orderPayment->getAccepted() === 1) {
            return $signer->signResponse(PaymentResponse::paid(), $shop);
        }

        return $signer->signResponse(PaymentResponse::cancelled('User canceled order'), $shop);
    }
}
```