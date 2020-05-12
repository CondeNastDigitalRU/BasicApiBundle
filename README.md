# Basic API Bundle
The bundle for rapid API development without writing boilerplate code.

[![Build Status](https://travis-ci.com/CondeNastDigitalRU/BasicApiBundle.svg?branch=master)](https://travis-ci.com/CondeNastDigitalRU/BasicApiBundle)

The main purpose of the bundle is to work with DTO: serialize, deserialize and validate, it does not know anything about the database and ORM.

Tasks solved by this bundle:
* Deserializing the request body from JSON into an object
* Validation of a deserialized object
* Serializing a response in JSON
* Serializing exceptions in JSON
* Extracting typed values from a query string
* API documentation generation

## Installation
```shell script
composer require condenast-ru/basic-api-bundle
```

Then bundle should be enabled in `bundles.php` file.

```php
<?php
# config/bundles.php

return [
    # ...
    Condenast\BasicApiBundle\CondenastBasicApiBundle::class => ['all' => true],
];
```

## How it works?
The bundle is based on symfony kernel event subscribers, they do the bulk of the work.
API actions are configured using the `Action` annotation in the controller.
Values from annotations are written to request attributes, which are then used by subscribers.
`symfony/serializer` is used for serialization and deserialization,
`symfony/validator` is used for validation,
`nelmio/api-doc-bundle` is used for API documentation generation.

## Usage
### API
* Describe how to serialize and deserialize your objects according to the `symfony/serializer` documentation
* Describe the validation rules for your objects according to the `symfony/validator` documentation
* Configure your controller with the `Action` annotation

Example:
```php
<?php declare(strict_types=1);

use Condenast\BasicApiBundle\Annotation as Api;
use Condenast\BasicApiBundle\Request\QueryParamFetcher;
use Condenast\BasicApiBundle\Tests\Fixtures\App\Entity\Article;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController
{
    /**
     * Create article
     *
     * @Route(
     *     "/articles",
     *     name="app.articles.post",
     *     methods={"POST"}
     * )
     * @Api\Action(
     *     resourceName="Article", # Resource name, used to group actions in the documentation
     *     request=@Api\Request(
     *         argument="article", # Controller method argument, the result of deserialization will be passed there
     *         type=Article::class, # Deserialization type, for example, Article or Article[] for an array of articles
     *         context={ # Request deserialization context
     *             "groups": "article.write",
     *         },
     *         validation=@Api\Validation(
     *             groups={"article.update"}), # Request validation groups
     *             sequence=true # Create a group sequence from groups. Default value is false
     *     ),
     *     response=@Api\Response(
     *         type=Article::class, # Response serialization type
     *         context={"groups": "article.detail"}, # Request serialization context
     *         statusCode=201 # The response code, it will be used if the controller returns something that is not an Symfony\Component\HttpFoundation\Response instance. Default value is 200
     *     )
     * )
     */
    public function postArticle(Article $article, QueryParamFetcher $queryParamFetcher): Article
    {
        // QueryParamFetcher can be used to extract typed values from request query parameters
        // To automatically inject a service into the controller method, add the `controller.service_arguments` tag in the service definition of your controller
        $queryParam = $queryParamFetcher->get('queryParam', null, QueryParamFetcher::TYPE_INT);
        $arrayQueryParam = $queryParamFetcher->get('queryParam', null, QueryParamFetcher::TYPE_INT, true);
        $nestedQueryParam = $queryParamFetcher->get('queryParam', '[nested]', QueryParamFetcher::TYPE_STRING);
        $nestedArrayQueryParam = $queryParamFetcher->get(
            'queryParam', # Query parameter name
            '[nested]', # Path to the property nested in the query parameter. Symfony PropertyAccessor notation is used
            QueryParamFetcher::TYPE_STRING, # Parameter type
            true # True if an array of values of the above type is expected
        );
                
        return $article;
    }
}
```

The controller can return the following values:
* An object, an array, everything that can be serialized in JSON, the response code will be taken from the Response annotation or the default value of 200 will be taken
* `Condenast\BasicApiBundle\Response\ApiResponse`, the value that is passed in the `$data` argument to the constructor will be serialized in JSON, you can also pass the response code and headers
* `Symfony\Component\HttpFoundation\Response`, nothing will be serialized, the answer will be returned as is

This bundle contains the normalizer and denormalizer for `ramsey/uuid`.

The bundle does not contain anything for CORS, if necessary, use `nelmio/cors-bundle`.

### API documentation
Install `nelmio/api-doc-bundle` and `symfony/twig-bundle` and configure according to the documentation,
the bundle describers will add to the documentation everything that can learn about the actions.
Everything missing could be added through the annotations of the controller, as written in the documentation for `nelmio/api-doc-bundle`.

## Development
To start a web server with a test application for development and debugging, use the `composer server` command.
The test application code is located in the `tests/Fixtures/App` directory.

## Tests
To run the tests, use the `composer tests` command.