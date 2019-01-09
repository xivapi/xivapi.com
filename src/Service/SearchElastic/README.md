# ElasticSearch Query Library
An abstraction layer over the official ElasticSearch PHP SDK.
## ElasticSearch CLI Commands
Make sure ElasticSearch is running, if it fails due to memory then make sure the JavaMemory and the: `/etc/elasticsearch/jvm.options` memory values are the same.

Other service commands:
- Restart: `sudo service elasticsearch restart`
- Stop: `sudo service elasticsearch stop`
- Start: `sudo service elasticsearch start`
- Test: `curl -X GET 'http://localhost:9200'`
- Delete all indexes: `curl -XDELETE 'http://localhost:9200/*'`
- list all indexes: `curl -X GET 'http://localhost:9200/_cat/indices?v'`
---

### Initialise
```php
$elastic = new \App\Service\ElasticSearch\ElasticSearch();
```
## Indexes
### Create
> It is recommended to use Dynamic Mapping! A template needs to be created for strings.
```php
$settings = [
	'analysis' => ElasticMapping::ANALYSIS
];

$category = ElasticMapping::NESTED;
$category['properties'] = [
	'ID' => ElasticMapping::INTEGER,
	'Name' => ElasticMapping::STRING
];

// index mappings
$mapping = [
	'search' => [
		'dynamic' => true,
		'_source' => [ 'enabled' => true ],
		'properties' => [
			'ID' => ElasticMapping::INTEGER,
			'Name' => ElasticMapping::STRING,
			'Level' => ElasticMapping::INTEGER,
			'Category' => $category,
		]
	],
];

// create index ($settings is optional)
$elastic->addIndex('item', $mapping, $settings);
```
### Delete
Including checking if it exists first
```php
$elastic->deleteIndex('item');
```
###  Check if exists
Checking if an index exists
```php
if ($elastic->isIndex('item')) {
	// .. do something ..
}
```
## Documents
### Add document
```php
$elastic->addDocument('item', 'search', '1675', [
	'ID' => '1675',
	'Name' => 'Curtana',
	'Type' => 'Weapon',
	'Level' => '50'
]);

$elastic->bulkDocuments('item', 'search', [
	'1675' => [
		'ID' => '1675',
		'Name' => 'Curtana',
		'Type' => 'Weapon',
		'Level' => '50'
	],
	'4000' => [
		'ID' => '4000',
		'Name' => 'Example',
		'Type' => 'Weapon',
		'Level' => '50'
	]
]);
```
### Get document
```php
$item = $elastic->getDocument('item', 'search', '1675');
```
### Delete document
```php
$elastic->deleteDocument('item', 'search', '1675');
```
## Query / Filters
Queries and filters are built using the class `ElasticQuery`, this is then provided to the method: `ElasticSearch->search($index, $type, $query)` method. There is also a `->count(...)` method for connivence. All values will be automatically set to lower case.
### all
```php
$query = (new ElasticQuery())->all();
$results = $elastic->search('item', 'search', $query);
```
#### Debug query
```php
print_r(
	json_encode(
		$query->getQuery(),
		JSON_PRETTY_PRINT
	)
);
```

### Query term
Must match 100% exactly.
```php
$query = (new ElasticQuery())->queryTerm('Name', 'Behemoth');
```
### Query wildcard
These would all find: **Behemoth Knives**
```php
$query = (new ElasticQuery())->queryWildcard('Name', 'Behemoth');
$query = (new ElasticQuery())->queryWildcard('Name', 'moth');
$query = (new ElasticQuery())->queryWildcard('Name', 'moth kni');
```
### Query prefix
Similar to wildcard but does not search to the left. So:
- `behemoth` would find **Behemoth Knives**.
- `knives` would not.
```php
$query = (new ElasticQuery())->queryPrefix('Name', 'behemoth');
```
### Query match
Must match the whole string, eg:
- `behemoth` would not find **Behemoth Knives**.
- `behemoth knives` would
```php
$query = (new ElasticQuery())->queryMatch('Name', 'Curtana');
```
### Query match phrase
Seems to work similar to match? Need to read elastic docs.
```php
$query = (new ElasticQuery())->queryMatchPhrase('Name', 'Curtana');
```
### Query match phrase prefix
Works similar to match but does not need to be the whole string. So much like **prefix**.
```php
$query = (new ElasticQuery())->queryMatchPhrasePrefix('Name', 'behemoth');
```
### Filter multi-range
Filter against multiple fields as range arguments
```php
$query = (new ElasticQuery())
    ->filterRange('LevelEquip', 40, 'gte')
    ->filterRange('LevelItem', 100, 'gte');
```
You can include string searches in with multiple queries
```php
$query = (new ElasticQuery())
	->queryWildcard('Name', 'sukan')
    ->filterRange('LevelEquip', 40, 'gte')
    ->filterRange('LevelItem', 100, 'gte')
    ->filterTerm('Col', 'X');
```
