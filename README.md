# OS2Forms KL forms

## Coding standards

```sh
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php7.4-fpm:latest composer2 install
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php7.4-fpm:latest composer2 coding-standards-check

docker run --rm --interactive --tty --volume ${PWD}:/app node:18 yarn --cwd /app install
docker run --rm --interactive --tty --volume ${PWD}:/app node:18 yarn --cwd /app coding-standards-check
```
