# OS2Forms KL forms

## Drush commands

```sh
drush --uri=… os2forms-kl-forms:generate kl_PN151 …/skemapakke/profile/KLB_ApplicationToCareForCloselyConnectedPersons_PN151.xsd --title='KLB_ApplicationToCareForCloselyConnectedPersons_PN151'
```

## Coding standards

```sh
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php7.4-fpm:latest composer2 install
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php7.4-fpm:latest composer2 coding-standards-check

docker run --rm --interactive --tty --volume ${PWD}:/app node:18 yarn --cwd /app install
docker run --rm --interactive --tty --volume ${PWD}:/app node:18 yarn --cwd /app coding-standards-check
```
