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
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class ShopFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $addon1 = (new ShopAddon())
            ->setName('Catering Guthaben 50€')
            ->setDescription('Starte die LAN mit einem kleinem Guthaben auf deiner Catering-Card.')
            ->setPrice(5000)
            ->setActive(true);

        $addon2 = (new ShopAddon())
            ->setName('Catering Guthaben 100€')
            ->setDescription('Starte die LAN mit einem großem Guthaben auf deiner Catering-Card.')
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
        $tickets[0] = (new Ticket())->setCode('CODE1-KRRUG-AAAAA')->setCreatedAt(new DateTimeImmutable('2023-09-25 13:37'))->setRedeemer($user14)->setRedeemedAt(new DateTimeImmutable('2023-09-26 14:21'));
        $tickets[1] = (new Ticket())->setCode('CODE1-KRRUG-BBBBB')->setCreatedAt(new DateTimeImmutable('2023-09-25 13:37'));
        $tickets[2] = (new Ticket())->setCode('CODE1-KRRUG-CCCCC')->setCreatedAt(new DateTimeImmutable('2023-09-25 13:37'));
        $tickets[3] = (new Ticket())->setCode('CODE1-KRRUG-DDDDD')->setCreatedAt(new DateTimeImmutable('2023-09-25 13:37'))->setRedeemer($user13)->setRedeemedAt(new DateTimeImmutable('2023-09-30 11:21'));
        $tickets[4] = (new Ticket())->setCode('CODE1-KRRUG-EEEEE')->setCreatedAt(new DateTimeImmutable('2023-09-25 13:37'))->setRedeemer($user18)->setRedeemedAt(new DateTimeImmutable('2023-09-30 11:21'));

        for ($i = 0; $i < count($tickets); $i++) {
            $t = $tickets[$i];
            $manager->persist($t);
            $this->setReference('ticket-'.$i, $t);
        }

        $now = new DateTimeImmutable();
        $now_m_10 = $now->sub(new DateInterval('P10M'));
        $now_m_20 = $now->sub(new DateInterval('P20M'));
        for ($i = 1; $i <= 10; $i++) {
            $user = Uuid::fromInteger(strval($i));
            $ticket = (new Ticket())->setCode(sprintf('00000-KRRUG-NR0%02d', $i))->setCreatedAt($now_m_20)->setRedeemer($user)->setRedeemedAt($now_m_10)->setPunchedAt($now);
            $manager->persist($ticket);
        }

        // one ticket and one extra
        $order1 = (new ShopOrder())
            ->setCreatedAt(new DateTimeImmutable('2023-07-21 05:05'))
            ->setOrderer($user14)
            ->setStatus(ShopOrderStatus::Paid)
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket($tickets[0])->setPrice(1337))
            ->addShopOrderPosition((new ShopOrderPositionAddon())->setAddon($addon1))
            ->addShopOrderHistory((new ShopOrderHistory())->setAction(ShopOrderHistoryAction::PaymentSuccessful)->setLoggedAt(new DateTimeImmutable('2024-07-21 05:10'))->setText('payment successfully done with credit card'))
        ;

        // three tickets (for discount)
        $order2 = (new ShopOrder())
            ->setCreatedAt(new DateTimeImmutable('2024-01-25 13:37'))
            ->setOrderer($user14)
            ->setStatus(ShopOrderStatus::Created)
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket(null)->setPrice(512))
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket(null)->setPrice(1337))
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket(null)->setPrice(1337))
        ;

        // paid one order
        $order3 = (new ShopOrder())
            ->setCreatedAt(new DateTimeImmutable('2024-02-02 18:27'))
            ->setOrderer($user13)
            ->setStatus(ShopOrderStatus::Paid)
            ->addShopOrderPosition((new ShopOrderPositionTicket())->setTicket($tickets[3])->setPrice(1337))
            ->addShopOrderHistory((new ShopOrderHistory())->setAction(ShopOrderHistoryAction::PaymentFailed)->setLoggedAt(new DateTimeImmutable('2024-07-02 20:00'))->setText('invalid card details'))
            ->addShopOrderHistory((new ShopOrderHistory())->setAction(ShopOrderHistoryAction::PaymentSuccessful)->setLoggedAt(new DateTimeImmutable('2024-07-03 05:15'))->setText('payment successfully done with credit card'))
        ;

        // cancelled order
        $order4 = (new ShopOrder())
            ->setCreatedAt(new DateTimeImmutable('2024-07-02 19:21'))
            ->setOrderer($user14)
            ->setStatus(ShopOrderStatus::Canceled)
            ->addShopOrderPosition((new ShopOrderPositionAddon())->setAddon($addon1))
            ->addShopOrderHistory((new ShopOrderHistory())->setAction(ShopOrderHistoryAction::OrderCanceled)->setLoggedAt(new DateTimeImmutable('2024-07-02 20:00'))->setText('cancelled by user')->setLoggedBy($user14))
        ;

        $manager->persist($order1);
        $manager->persist($order2);
        $manager->persist($order3);
        $manager->persist($order4);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SettingsFixture::class];
    }
}
