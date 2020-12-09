# Command Information

### Big import script

```
bash bin/update_live 1
```

The `1` at the end is "full or not"
- `1` = perform full import
- `0` = a 0 or missing is a quick import


### Data Import

```
php bin/console SaintCoinachRedisCommand <commands>
```

- `--start=x` (0 = default) The file count number to start at
- `--count=x` (1000 = default) The number of files to process
- `--fast=1` (1 = default) if to skip all "Questions", very rarely need not to
- `--full=0` (0 = default) if to perform a full import and override old data
- `--content=Item` (null = default) perform import on a specific piece of content
- `--id=1675` (null = default) perform import on a specific content id


Example usage:

```
php bin/console SaintCoinachRedisCommand --start=0 --count=500 --full=0 --content=GrandCompany -q
```

### ElasticSearch Import

```
php bin/console UpdateSearchCommand <commands>
```

- `--environment=prod` (prod = default) which environment to import, only "dev" if you run locally
- `--full=0` (0 = default) if to perform a full import and override old data
- `--content=Item` (null = default) perform import on a specific piece of content
- `--id=1675` (null = default) perform import on a specific content id
