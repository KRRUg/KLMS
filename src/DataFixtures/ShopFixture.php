<?php

namespace App\DataFixtures;

use App\Entity\ShopAddon;
use App\Entity\ShopOrder;
use App\Entity\ShopOrderHistory;
use App\Entity\ShopOrderHistoryAction;
use App\Entity\ShopOrderPositionAddon;
use App\Entity\ShopOrderPositionTicket;
use App\Entity\ShopOrderStatus;
use App\Entity\Ticket;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class ShopFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $addon1 = (new ShopAddon())
            ->setName('Catering Guthaben 50€')
            ->setPrice(5000)
            ->setActive(true);

        $addon2 = (new ShopAddon())
            ->setName('Catering Guthaben 100€')
            ->setPrice(10000)
            ->setActive(true);

        $addon3 = (new ShopAddon())
            ->setName('VIP Seat')
            ->setPrice(1337)
            ->setActive(false);

        $manager->persist($addon1);
        $manager->persist($addon2);
        $manager->persist($addon3);

        $user13 = Uuid::fromInteger(strval(13));
        $user14 = Uuid::fromInteger(strval(14));
        $user18 = Uuid::fromInteger(strval(18));

        $tickets = array();
        $tickets[0] = (new Ticket())->setCode('IPOXY-KRRUG-AAAAA')->setCreatedAt(new \DateTimeImmutable('2023-09-25 13:37'))->setRedeemer($user14);
        $tickets[1] = (new Ticket())->setCode('ADBE3-KRRUG-BBBBB')->setCreatedAt(new \DateTimeImmutable('2023-09-25 13:37'))->setRedeemer(null);
        $tickets[2] = (new Ticket())->setCode('IEZ89-KRRUG-CCCCC')->setCreatedAt(new \DateTimeImmutable('2023-09-25 13:37'))->setRedeemer(null);
        $tickets[3] = (new Ticket())->setCode('IKP81-KRRUG-DDDDD')->setCreatedAt(new \DateTimeImmutable('2023-09-25 13:37'))->setRedeemer($user13);
        $tickets[4] = (new Ticket())->setCode('32FSB-KRRUG-EEEEE')->setCreatedAt(new \DateTimeImmutable('2023-09-25 13:37'))->setRedeemer($user18);

        for ($i = 0; $i < count($tickets); $i++) {
            $t = $tickets[$i];
            $manager->persist($t);
            $this->setReference('ticket-'.$i, $t);
        }

        for ($i = 1; $i <= 10; $i++) {
            $user = Uuid::fromInteger(strval($i));
            $ticket = (new Ticket())->setCode(sprintf('00000-KRRUG-NR0%02d', $i))->setCreatedAt(new \DateTimeImmutable())->setRedeemer($user);
            $manager->persist($ticket);
        }

        // one ticket and one extra
        $order1 = (new ShopOrder())
            ->setCreatedAt(new \DateTimeImmutable('2024-07-21 05:05'))
            ->setOrderer($user14)
            ->setStatus(ShopOrderStatus::Paid)
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket($tickets[0]))
            ->addShopOrderPosition((new ShopOrderPositionAddon())->setAddon($addon1))
            ->addShopOrderHistory((new ShopOrderHistory())->setAction(ShopOrderHistoryAction::PaymentSuccessful)->setLoggedAt(new \DateTimeImmutable('2024-07-21 05:10'))->setText('payment successfully done with credit card'))
        ;

        // three tickets (for discount)
        $order2 = (new ShopOrder())
            ->setCreatedAt(new \DateTimeImmutable('2023-09-25 13:37'))
            ->setOrderer($user13)
            ->setStatus(ShopOrderStatus::Created)
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket(null))
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket(null))
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket(null))
        ;

        // paid one order
        $order3 = (new ShopOrder())
            ->setCreatedAt(new \DateTimeImmutable('2024-07-02 18:27'))
            ->setOrderer($user13)
            ->setStatus(ShopOrderStatus::Paid)
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket($tickets[3]))
            ->addShopOrderHistory((new ShopOrderHistory())->setAction(ShopOrderHistoryAction::PaymentFailed)->setLoggedAt(new \DateTimeImmutable('2024-07-02 20:00'))->setText('invalid card details'))
            ->addShopOrderHistory((new ShopOrderHistory())->setAction(ShopOrderHistoryAction::PaymentSuccessful)->setLoggedAt(new \DateTimeImmutable('2024-07-03 05:15'))->setText('payment successfully done with credit card'))
        ;

        // cancelled order
        $order4 = (new ShopOrder())
            ->setCreatedAt(new \DateTimeImmutable('2024-07-02 19:21'))
            ->setOrderer($user14)
            ->setStatus(ShopOrderStatus::Canceled)
            ->addShopOrderPosition((new ShopOrderPositionAddon())->setAddon($addon1))
            ->addShopOrderHistory((new ShopOrderHistory())->setAction(ShopOrderHistoryAction::OrderCanceled)->setLoggedAt(new \DateTimeImmutable('2024-07-02 20:00'))->setText('cancelled by user')->setLoggedBy($user14))
        ;

        $manager->persist($order1);
        $manager->persist($order2);
        $manager->persist($order3);
        $manager->persist($order4);

        $manager->flush();
    }
}
