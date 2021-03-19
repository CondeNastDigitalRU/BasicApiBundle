# Basic API Bundle
The bundle for rapid API development without writing boilerplate code

[![Build Status](https://travis-ci.com/CondeNastDigitalRU/BasicApiBundle.svg?branch=master)](https://travis-ci.com/CondeNastDigitalRU/BasicApiBundle)

The main purpose of the bundle is to work with DTOs: serialization, deserialization and validation.
It doesn't know anything about the database and ORM.

Tasks solved by this bundle:
* Deserializing the request body from JSON to an object or array of objects
* Validating the deserialization result
* Serializing the response to JSON
* Serializing exceptions to JSON
* Extracting typed values from a query string
* Generating API documentation

## Installation
```shell script
composer require condenast-ru/basic-api-bundle
```

Then bundle should be enabled in `bundles.php` file

```php
<?php declare(strict_types=1);
# config/bundles.php

return [
    # ...
    Condenast\BasicApiBundle\CondenastBasicApiBundle::class => ['all' => true],
];
```

## How it works?
The bundle is based on symfony kernel event subscribers, they do the bulk of the work.
API actions are configured using annotations in the controller.
Values from annotations are written to request attributes, which are then used by subscribers.
`symfony/serializer` is used for serialization and deserialization,
`symfony/validator` is used for validation,
`nelmio/api-doc-bundle` is used for API documentation generation.

## Usage
### API
* Describe how to serialize and deserialize your objects according to the `symfony/serializer` documentation
* Describe the validation rules for your objects according to the `symfony/validator` documentation
* Configure your controller actions using the annotations provided with this bundle  

Example:

```php
<?php declare(strict_types=1);

use Condenast\BasicApiBundle\Annotation as Api;
use Condenast\BasicApiBundle\Request\QueryParamBag;
use Condenast\BasicApiBundle\Response\Payload;
use Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Article;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @Api\Resource("Article") # Resource name used to group actions in API documentation
     * @Api\Deserialization(
     *     argument="article", # The argument of the controller method, the result of deserialization will be passed there
     *     type=Article::class, # The type of deserialization, such as Article or Article [] for an array of articles
     *     context={"groups": "article.write"} # Deserialization context
     * )
     * @Api\Validation(
     *     groups={"article.write"} # Validation groups
     * )
     * @Api\QueryParam(
     *     name="tags", # The name by which the parameter will be available in the QueryParamBag
     *     path="extra.tags", # The path to the parameter in the request, if not specified, will be equal to the name.
     *     map=true, # Whether the parameter is an array
     *     constraints={ # Validation constraints
               @Assert\Length(min=2),
     *     },
     *     default={}, # Default parameter value
     *                 # If not specified, then null or an empty array, depending on whether the parameter is declared as an array
     *                 # If the parameter value does not meet the requirements, the default value will be returned
     *     description="Tags to associate with", # Description, for an API documentation only
     *     format="uuid" # Format, for an API documentation only
     * )
     * @OA\Response( # Response description, for an API documentation only
     *     response=201,
     *     description="Created article",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Nelmio\Model(type=Article::class, groups={"article.read"})
     *     )
     * )
     */
    public function postArticle(Article $article, QueryParamBag $query): Payload
    {
        $tags = $query->get('tags');
    
        return new Payload($article, 201, ['groups' => 'article.read']);
    }
}
```
### CORS
The bundle does not contain anything for CORS, if necessary, use `nelmio/cors-bundle`.

### API documentation
Install `nelmio/api-doc-bundle` and `symfony/twig-bundle` and configure according to the documentation,
bundle describers will add anything they can learn about actions to the documentation.
Anything that is missing can be added via annotations to the controller, as written in the documentation
for the `nelmio/api-doc-bundle`.

## Development
To run a web server with a test application for development and debugging, make sure the Symfony CLI is installed
and run the command `make server`.
The test application code is located in the `tests/Fixtures/App` directory.

## Tests
To run the tests, use the `make tests` command.
