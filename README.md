# ez-content-decorator

## Introduction

Lorsqu'on travaile sur des templates, eZ Publish fournit généralement 2 objects :
* location : représentant la localisation dans l'arborescence
* content : représentant le contenu proprement dit

Ces 2 éléments suffisent dans la majorité des cas, mais très vite, le besoin d'étendre ces 2 objets se fait ressentir.

Un exemple classique consiste à récupérer dans le controleur la liste des enfants d'une location à travers une LocationQuery, on va le paginer et le passer à la vue.

La vue affichera un listing avec un chapo, le chapo sera constitué soit du champ chapo soit du champ du contenu principal coupant à un certains nombres de caractères.

Bref, un grand classique du Web.

Classiquement, on va répéter ses opérations des dizaines et dizaines de fois. Et, comme il y a plusieurs développeurs sur le projets, chacun va avoir des petites différences :
* entre ceux qui préfère exclure les types de contenus qui ne conviennent pas, et ceux qui préfère inclure uniquement ceux qui conviennet
* entre ceux qui préfère calculer le maximum de chose dans un controleur et ceux vont déléguer à des sous requetes
 
On a trouvé un manque d'harmonie et des copier / coller d'un type de contenu à l'autre, et à chaque changement c'est le bal des retours lients interminables, en gros, on a un terreau à dette technique.
 
## L'idée : un décorator

Ce bundle fournit un mécanisme très simple pour apporter une solution concrète à cette problématique, il permet d'avoir très simplement et très rapidements des décorator qui encapsule nos types de contenus dans une structure plus souple.

L'idée est simple, il s'agit d'abord de regrouper sous une même classe les éléments qui constituent un contenu :
* la location
* le content

C'est la classe ContentDecorator. Bon, ok cela ne sert pas à grand chose.

Cette classe va nous servir de classe de base pour définir nos decorators pour les instance concrète.

```php
class Article extends ContentDecorator {
     public function getPropertyArticle()
}
class Blog extends ContentDecorator {
     public function getPropertyBlog()
}
```

Chaque classe permet de définir des méthodes qui sont propres à chaque type de contenu.

Enfin, on exploite la configuration sémantique pour faire le lien entre la classe et type de contenu.

```yaml
ezcontentdecorator.global.class_mapping:
  article: \<vendor\<mon_site_bundle>\ContentType\Article
  blog: \<vendor\<mon_site_bundle>\ContentType\Blog
```
  
On notera que l'on peux avoir un mapping différent par siteaccess grâce à la configuration sémantique. Ce qui peut être très pratique dans des
 plate-forme multisites.
 
Le service ContentDecoratorFactory permet d'instancier la bonne classe en fonction du type de contenu.

```php
$article = $contentDecoratorFactory->getContentDecorator($articleLocation);
echo $article->getPropertyArticle();

$blog = $contentDecoratorFactory->getContentDecorator($blogLocation);
echo $blog->getPropertyBlog();
```

## Chargement automatique

A ce stade, pour exploiter ce mécanisme, il faut :

* charger le décorateur à chaque appel dans le contrôleur
* charger le décorateur directement dans la vue via un opérateur Twig custom

Ce n'est pas terrible comme solution, surtout qu'eZ Publish fournit, un Event le PreContentView Listener qui nous permettre de charger automatiquement le decorateur et injecter les variables dans le template.

```php
/**
 * @param PreContentViewEvent $event
 */
public function onPreContentView( PreContentViewEvent $event )
{
    $contentView = $event->getContentView();

    /** @var \Truffo\eZContentDecoratorBundle\Decorator\ContentDecoratorFactory $contentDecoratorFactory */
    $contentDecoratorFactory = $this->container->get('ezcontentdecorator.services.factory');

    if ($contentView->hasParameter('location')) {
        $location = $contentView->getParameter('location');
        /** @var \Truffo\eZContentDecoratorBundle\Decorator\ContentDecorator $contentDecorator */
        $contentDecorator = $contentDecoratorFactory->getContentDecorator($location);
        $contentView->addParameters([
            $contentDecorator->getContentTypeIdentifier() => $contentDecorator,
            'decorator' => $contentDecorator
        ]);
    }
}
```
On a ainsi 2 variables qui sont automatiquement disponibles dans tout les templates qui ont une Location en paramètre :

* <nom_du_type_de_contenu> 
* decorator

Elles sont identiques, ce sont juste des sucres syntaxiques.

Ainsi dans une vue full d'un article, on pourrait avoir le code suivant :

```twig
{{ ez_render_field(article.content, "title") }}
{{ article.propertyArticle }}
```

ou son équivalent
```twig
{{ ez_render_field(decorator.content, "title") }}
{{ decorator.propertyArticle }}
```

## Chargement automatique dans les listes

On a trouver une solution pour charger automatiquement dans les vues contenant une location. Maintenant, pour les résultats de recherche, c'est presque aussi simple.

Une bonne pratique consiste à utiliser à systématiquement un pager (PagerFanta) pour récupérer les résultats d'une query via le SearchService.
Ce composant à l'avantage de permettre d'injecter des adpaters.

Le LocationDecoratorSearchAdapter injecte automatiquement notre décorator à chaque instance des locations retournées.

Dans nos repositories, on peux avoir quelques choses qui ressemble à cela :

```php
public function getArticleList($location, $limit = 10, $page = 1)
{
    $query = new LocationQuery();
    $query->criterion =
    new Criterion\LogicalAnd(array(
         new Criterion\ContentTypeIdentifier(['article']),
         new Criterion\Subtree($location->pathString),
         new Criterion\Visibility(Criterion\Visibility::VISIBLE)
    ));
    $query->sortClauses = [  new SortClause\Field('article', 'publication_date', Query::SORT_DESC, 'fre-FR') ];
    return LocationDecoratorSearchAdapter::buildPager($query, $searchService, $contentDecoratorFactory
    , $limit, $page)
}
```

et dans notre controlleur

```php
$params += ['items' => $helper->getArticleList($location)];  
return $this->get('ez_content')->viewLocation($locationId, $viewType, $layout, $params);
```

Ainsi dans notre vue, on a directement des décorateurs :

```twig
{% for item in items %}
    {{ ez_render_field(item.content, "title") }}
    {{ decorator.propertyArticle }} 
{% endfor %}
```

## La réutilisabilité horizontale avec les traits

Les mécanismes présentés ci-dessus sont encore plus puissants si on utilise les égalements les traits de PHP.
Reprenons notre exemple du chapo, on va pouvoir écrire quelques choses qui ressemble à ceci :

```php
interface Chapoable
{
     public function chapo();
}

traits Chapo {
     public function chapo()
     {
         // Notre logique pour construire un chapo ...
     }
}

class Article implements Chapoable {
     use Chapo;
}

class Page implements Chapoable
{
     use Chapo;
}
```

```twig
{{ article.chapo }}
```

Une bonne convention sur le nommage des champs dans les différents type de contenu, avec ce bundle et les traits. On peux exploiter au maximum la réutilisabilité et la concision du code, CQFD.






 
 
 
