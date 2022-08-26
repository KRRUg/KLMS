<?php

namespace App\Form;

use DOMDocument;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HtmlHandlingSubscriber implements EventSubscriberInterface
{
    private array $fields;
    private array $options;
    private String $urlRegex;

    public const RELATIVE_URLS = 'relative_urls';
    public const CLEAR_CLASSES = 'clear_classes';
    public const CLEAR_SCRIPTS = 'clear_scripts';

    public function __construct(UrlGeneratorInterface $router)
    {
        $context = $router->getContext();
        $baseUrl = str_replace("/", "\\/", $context->getBaseUrl());
        $this->urlRegex = "/^(({$context->getScheme()}:\\/\\/)?{$context->getHost()}(:{$context->getHttpPort()}|:{$context->getHttpsPort()})?)?{$baseUrl}/";

        // TODO change this once HtmlTextAreaType is there
        $this->fields = ['content'];

        // TODO move option resolver to HtmlTextAreaType
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve([]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault(self::RELATIVE_URLS, true)
            ->setDefault(self::CLEAR_SCRIPTS, true)
            ->setDefault(self::CLEAR_CLASSES, true)
            ->setAllowedTypes(self::RELATIVE_URLS, 'bool')
            ->setAllowedTypes(self::CLEAR_SCRIPTS, 'bool')
            ->setAllowedTypes(self::CLEAR_CLASSES, 'bool')
        ;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit'
        ];
    }

    public function onPreSubmit(PreSubmitEvent $event)
    {
        $data = $event->getData();
        foreach ($this->fields as $field) {
            if (!$event->getForm()->has($field))
                continue;
            $doc = new DOMDocument();
            try{
                // add dummy div around content, to have one root node
                // otherwise $crawler->html() does crazy stuff
                $html = "<div>{$data[$field]}</div>";
                $doc->loadHTML(
                    mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
                    LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
                );
            } catch (\ErrorException $exception) {
                // TODO handle if html is not valid
                $doc->loadHTML('<p></p>');
            }
            $crawler = new Crawler($doc);
            $this->relativeUrls($crawler);
            $this->clearScripts($crawler);
            $data[$field] = $crawler->html();
        }
        $event->setData($data);
    }

    private function relativeUrls(Crawler $crawler)
    {
        if (!$this->options[self::RELATIVE_URLS])
            return;

        $target = ['a' => 'href', 'img' => 'src'];

        foreach ($target as $item => $attr) {
            foreach ($crawler->filter($item) as $node) {
                $url = $node->getAttribute($attr);
                // remove base url
                $url = preg_replace($this->urlRegex, '', $url);
                $node->setAttribute($attr, $url);
            }
        }
    }

    private function clearScripts(Crawler $crawler)
    {
        if (!$this->options[self::CLEAR_CLASSES])
            return;

        foreach ($crawler->filter('script') as $node) {
            $node->parentNode->removeChild($node);
        }
    }
}