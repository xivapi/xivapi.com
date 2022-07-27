# XIVAPI.com  
  
This is the source code in all its PHP mess for xivapi.com and built using Symfony 4 Slim. Use at your own risk!
  
> **Note regarding contributions:**  I am not really looking for development contributions to core features as this time (data building and presenting) because I will be splitting much of the logic out into their own small dedicated micro-services. This will allow developers to just focus on small bits if they choose or if they only care about using 1 feature in their own tools.  
  
![](https://xivapi.com/discord/name_purple.png)
 
### [Join us on Discord!](https://discord.gg/MFFVHWC)  

---  

### Setting up  
  
- You need vagrant.  
- `cd /vm`  
- `vagrant plugin install vagrant-hostmanager`  
- `vagrant up`  
- Access: http://xivapi.local  
- If you have `adminer.php` in `/sysops` you can access: http://xivapi.adminer  
  
### ElasticSearch commands  
  
- Restart: `sudo systemctl restart elasticsearch`  
- Stop: `sudo systemctl stop elasticsearch`  
- Start: `sudo systemctl start elasticsearch`  
- Test: `curl -X GET 'http://localhost:9200'`  
- Delete all indexes: `curl -XDELETE 'http://localhost:9200/*'`  
- list all indexes: `curl -X GET 'http://localhost:9200/_cat/indices?v'`
  
---  
  
The list below shows all current micro-services in development.  
  
| Service | Repository | Notes |  
| --- | --- | --- |  
| Game Data Builder | https://github.com/xivapi/xivapi-data | Build the game data automatically using SaintCoinach Schema |  
| Game Search | - | This will split out the ElasticSearch logic from XIVAPI.com into its own dedicated microservice. |  
| Mog (DiscordBot) | https://github.com/xivapi/xivapi-mog | A PHP Discord bot, a bot that will message a channel either via running CLI command or can even be done via REST, I use this to announce deployment info. I may use it for other interacts, it was more of a concept that turned out quite good |  
| Mappy | https://github.com/xivapi/xivapi-mappy - https://xivapi.com/docs/Mappy-Data | A C# App that provides various memory information of NPCs, Enemies and Objects to XIVAPI. Good for building FFXIV Maps! |  
| Tooltips | https://github.com/xivapi/xivapi-tooltips | A concept for getting information for FFXIV tooltips. Very early stages |  
| Custom Launcher | https://github.com/xivapi/ffxiv-launcher | This is not XIVAPI specific however I do want to include features that talk to XIVAPI such as dev posts, lodestone news, character and friend information, etc. |  
| Lodestone Parser | https://github.com/xivapi/lodestone-parser | A HTML parser written in PHP, parses Lodestone, simple! |  
  
If you are interested in building a service using the API, there are some libraries:  
  
| Language | Repository |  
| --- | --- |  
| Golang | https://github.com/xivapi/xivapi-go |  
| Ruby | https://github.com/xivapi/xivapi-ruby |  
| JavsScript | https://github.com/xivapi/xivapi-js |  
| Angular | https://github.com/xivapi/angular-client |  
| PHP (WIP) | https://github.com/xivapi/xivapi-php |  
  
Maybe online API's are not your jam, maybe you're sick of the responses and how hard it is to build models, have you considered a datamining career!? Here are some awesome resources:  
  
| Title | URL | About |  
| --- | --- | --- |  
| SaintCoinach | https://github.com/xivapi/SaintCoinach | The worlds best datamining tool, gets everything you'll ever need, including resources to make conspiracy theorists go crazy |
| Datamining | https://github.com/viion/ffxiv-datamining | A resource for sharing datamining information, all sorts of stuff in here. |  
| Sapphire | https://github.com/SapphireMordred/Sapphire | FFXIV Server Emulation |  
| Patch Extractions Archive | https://github.com/viion/ffxiv-datamining-patches | A CSV extraction of every patch since 2.0 (except 2.1... dont ask) |  
| 3D Viewer | https://github.com/viion/ffxiv-3d | Example of making a 3D web viewer using obj files from TexTools2 |  
| LibPomPom | https://github.com/Minoost/libpompom-sharp | C# App for talking to the CompanionApp API |  
| TexTools 2 by Liinko | https://github.com/liinko/FFXIV_TexTools2 | Awesome tools for viewing data, extracting materials and 3d models and importing nude mods |  
| ClassJob Icons | https://github.com/xivapi/xivapi-classjobs | A collection of HQ class job icons |  
| FFXIV Memory Reader | https://github.com/TamanegiMage/FFXIV_MemoryReader | Reads your mind |  
| Machina | https://github.com/ravahn/machina | Reads the servers mind (Network packet reader) |  
| Sharlayan | https://github.com/FFXIVAPP/sharlayan | Read more minds (Memory Reader) |  
| Quick Search | [xivapi-quick-search](https://chrome.google.com/webstore/detail/xivapi-quick-search/lgefpgdbbmcahllbifniibndmoignmfg) | A google chrome extension for quickly searching xivapi |

- Game Data: https://github.com/xivapi/xivapi-data

## Setting up

- You need vagrant.
- `cd /vm`
- `vagrant plugin install vagrant-hostmanager`
- `vagrant up`
- Access: http://xivapi.local
- If you have `adminer.php` in `/sysops` you can access: http://xivapi.adminer

## ElasticSearch commands

- Restart: `sudo systemctl restart elasticsearch`
- Stop: `sudo systemctl stop elasticsearch`
- Start: `sudo systemctl start elasticsearch`
- Test: `curl -X GET 'http://localhost:9200'`
- Delete all indexes: `curl -XDELETE 'http://localhost:9200/*'`
- list all indexes: `curl -X GET 'http://localhost:9200/_cat/indices?v'`
