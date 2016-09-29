# DaaS

Documentation as a Service. A sane documentation tool with built-in versioning support.

## Setup

```bash
git clone git@github.com:kelunik/daas && cd daas

# Install dependencies using Composer
composer install

# Configure your environment, you need Elasticsearch and MySQL
cp .env.example .env

# Create the required MySQL tables
vendor/bin/phinx migrate

# Import your first project, this creates the Elasticsearch index automatically
bin/github-import amphp amp

# Run the application server
vendor/bin/aerys -c src/aerys.php
```
