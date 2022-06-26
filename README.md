# DesignTechnologies Interview task

## Notes

I assume the amounts in the CSV are in the highest currency unit (ex. Euro not cents).
I added support for using cents if you pass a decimal number: 42.15 EUR = 42 euro 15 cents.

Each currency conversion is rounded to the lowest currency unit with HALF UP method.
For example if a conversion result is `10.126` it becomes `10.13` while `10.123` becomes `10.12`

The currency conversion is done at each document (if needed), not at the final result.

## Requirements

- Docker 

or

- PHP 8.1 with the required extensions from `composer.json`
- Symfony CLI

## Building the project

### Container build

Make sure you are in the root project directory and run:

`docker build . -t designtech-interview-task`

### Installing project dependencies

`docker run -itu 1000 --rm -v "$PWD":/app -w /app designtech-interview-task composer install`

## How to run the project

Make sure you have executed the required steps in [Building the project](#building-the-project)

`docker run -itu 1000 -p 8080:8080 --rm -v "$PWD":/app -w /app designtech-interview-task symfony server:start --port=8080`

Now the project should be accessible on http://127.0.0.1:8080/

The documentation page, containing swagger UI with the provided docs can be found here: http://localhost:8080/docs 

On the swagger UI documentation page you should be able to test the API as it's fully compatible as requested.

## Executing the automated tests

`docker run -itu 1000 --rm -v "$PWD":/app -w /app designtech-interview-task bin/phpunit --coverage-html .code-coverage`

After executing the tests, the code coverage report will be available under `.code-coverage` directory.

## Debug the project (on linux only)

Add to the docker run command:

`--add-host=host.docker.internal:host-gateway` so xdebug can connect to the host IDE

## Static code analysis tools

### PHP Mess Detector

`docker run -itu 1000 --rm -v "$PWD":/app -w /app designtech-interview-task vendor/bin/phpmd src text phpmd.xml`

### PHP Code Sniffer

`docker run -itu 1000 --rm -v "$PWD":/app -w /app designtech-interview-task vendor/bin/phpcs -p`

### Psalm with error level 1 (the highest possible)

`docker run -itu 1000 --rm -v "$PWD":/app -w /app designtech-interview-task vendor/bin/psalm`
