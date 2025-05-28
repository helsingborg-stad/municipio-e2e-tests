# Municipio Smoke Tests
Smoke tests for Municipio theme. This repository contains tests that can be run against a Municipio website to ensure that the website is working as expected. Please note that these tests are not exhaustive and should be used as a starting point for testing.

## Prerequisites
* A Municpio website to run the tests against.
* A sitemap available on the website.

## Run from local machine
1. Requires PHP 8.3 or higher.
1. Clone this repository to your local machine.
1. Run `composer install` to install dependencies.
1. Run `composer test:local` to run the tests.

> [!NOTE]
> Please ensure that you have the necessary environment variable `SITEMAP_URLS` set before running the tests. This variable should contain the sitemap URL or a list of comma-separated URLs.

## Run from GitHub Actions
1. Add a new workflow file to your repository in `.github/workflows/` directory.
1. Add the following content to the file:
```yaml
name: Run Municipio Smoke Tests

on:
  workflow_dispatch: # Update this to your desired trigger event

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
    steps:
      - name: Run Tests
        uses: helsingborg-stad/municipio-smoke-tests@v1
        with:
          sitemap-urls: ${{ vars.SITEMAP_URLS }}
```

### Running in shards
If you want to run the tests in shards, which is useful when you have a large number of URLs to test, you can use the `shard` and `total-shards` inputs. Here is an example of how you can run the tests in shards:
```yaml
jobs:
  test:
    strategy:
      fail-fast: false
      matrix:
        shard: [1, 2, 3, 4]
        total-shards: [4]
    runs-on: ubuntu-latest
    steps:
      - name: Run Municipio Smoke Tests
        uses: helsingborg-stad/municipio-smoke-tests@v1
        with:
          sitemap-urls: ${{ vars.SITEMAP_URLS }}
          shard: ${{ matrix.shard }}
          total-shards: ${{ matrix.total-shards }}
```

### Available inputs for the action
```yaml
sitemap-urls:
    description: 'A comma-separated list of URLs to test'
    required: true
shard:
    description: 'The shard index to run'
    default: 'false'
    required: false
total-shards:
    description: 'The total number of shards'
    default: 'false'
    required: false
```

