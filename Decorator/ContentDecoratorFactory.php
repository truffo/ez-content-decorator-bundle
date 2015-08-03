<?php

namespace Truffo\eZContentDecoratorBundle\Decorator;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use Symfony\Component\DependencyInjection\ContainerInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class ContentDecoratorFactory
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface $container;
     */
    private $container;

    /**
     * @var \eZ\Publish\API\Repository\Repository $repository;
     */
    private $repository;


    /** @var string $defaultClassName  */
    private $defaultClassName;


    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver */
    private $configResolver;


    public function __construct(ContainerInterface $container, ConfigResolverInterface $configResolver)
    {
        $this->container = $container;
        $this->configResolver = $configResolver;
        $this->repository = $this->container->get('ezpublish.api.repository');
        $this->defaultClassName = $this->configResolver->getParameter('default_class', 'ezcontentdecorator');
    }

    public function getContentDecorator(Location $location)
    {
        $mappingEntities = $this->configResolver->getParameter('class_mapping', 'ezcontentdecorator');
        $contentTypeIdentifier = $this->repository->getContentTypeService()
            ->loadContentType($location->contentInfo->contentTypeId)->identifier;
        $className =  (array_key_exists($contentTypeIdentifier, $mappingEntities)) ?
            $mappingEntities[$contentTypeIdentifier] : $this->defaultClassName;
        return new $className($this->container, $location, $contentTypeIdentifier);
    }
    
    public function getContentDecoratorByContent(Content $content)
    {
        $location = $this->repository->getLocationService()->loadLocation($content->contentInfo->mainLocationId);
        return $this->getContentDecorator($location);
    }

}
