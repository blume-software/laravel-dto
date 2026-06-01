# blume-software/laravel-dtovel-dto

Laravel DTO library with validation attributes, casts, response helpers, and OpenAPI spec generation.

## Installation

```bash
composer require blume-software/laravel-dtovel-dto
```

## OpenAPI

Set `OPENAPI_ENABLED=true` in your environment to expose:

- `GET /openapi` — JSON spec
- `GET /openapi/ui` — Swagger UI

## Local Development

Local development against a consuming app
When you are adding a feature here and want to try it in an app before pushing to Git, use a Composer path repository so
the app symlinks to your local clone.

### 1. Clone this repo locally

```shell
git clone git@github.com:blume-software/laravel-dto.git
cd laravel-dto
git checkout -b my-feature
```

### 2. Point the app at your local clone

In the app’s composer.json, temporarily replace the VCS/Packagist source with:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../laravel-dto",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "blume-software/laravel-dtovel-dto": "@dev"
  }
}
```

Adjust url to the relative or absolute path to your clone.

Then in the app:

```shell
composer update blume-software/laravel-dtovel-dto
```

Composer symlinks vendor/blume-software/laravel-dtovel-dto → your working tree. Edits in this repo are picked up immediately by the
app (no push, no composer update per change).

**Keep path-repo overrides local or on a dev branch so CI/production always install from Git/tags.**

## License

MIT
