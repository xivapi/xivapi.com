# Command Information

### Data Import

```
php bin/console SaintCoinachRedisCommand <commands>
```

- `--start=x` The file count number to start at
- `--count=x` The number of files to process
- `--fast=1` (1 = default) if to skip all "Questions", very rarely need not to
- `--full=0` (0 = default) if to perform a full import and override old data
- `--content=Item` (null = default) perform import on a specific piece of content
- `--id=1675` (null = default) perform import on a specific content id


### ElasticSearch Import

```
php bin/console UpdateSearchCommand <commands>
```

- `--environment=prod` (prod = default) which environment to import, only "dev" if you run locally
- `--full=0` (0 = default) if to perform a full import and override old data
- `--content=Item` (null = default) perform import on a specific piece of content
- `--id=1675` (null = default) perform import on a specific content id
