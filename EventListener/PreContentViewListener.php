<?php

namespace Truffo\eZContentDecoratorBundle\EventListener;

use eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PreContentViewListener implements ContainerAwareInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function onPreContentView( PreContentViewEvent $event )
    {
        $contentView = $event->getContentView();

        /** @var \Mousetic\Bundle\ContentDecoratorBundle\Decorator\ContentDecoratorFactory $contentDecoratorFactory */
        $contentDecoratorFactory = $this->container->get('contentdecorator.services.factory');

        /** @var \Mousetic\Bundle\ContentDecoratorBundle\Decorator\ContentDecorator $contentDecorator */
        $contentDecorator = $contentDecoratorFactory->getContentDecorator($contentView->getParameter('location'));
        $contentView->addParameters([
            $contentDecorator->getContentTypeIdentifier() => $contentDecorator,
            'decorator' => $contentDecorator
        ]);
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}