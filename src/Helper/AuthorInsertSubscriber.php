<?php

namespace App\Helper;

use App\Security\LoginUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

class AuthorInsertSubscriber implements EventSubscriberInterface
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit'
        ];
    }

    public function onPostSubmit(PostSubmitEvent $event)
    {
        $data = $event->getData();
        $user = $this->security->getUser();

        if (!($data instanceof HistoryAwareEntity) || !($user instanceof LoginUser)) {
            return;
        }

        $user = $user->getUser();
        $uuid = $user->getUuid();
        if (empty($data->getAuthorId())) {
            $data->setAuthorId($uuid);
        }
        $data->setModifierId($uuid);
        $event->setData($data);
    }
}