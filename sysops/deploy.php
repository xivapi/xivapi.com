<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

argument('composer', InputArgument::OPTIONAL, 'Run: Composer Update', false);

// Project name
set('application', 'xivapi');
set('repository', 'https://github.com/xivapi/xivapi.com');
set('ssh_multiplexing', false);
inventory('deploy-hosts.yml');

// --------------------------------------------------------

function result($text)
{
    $text = explode("\n", $text);

    foreach($text as $i => $t) {
        $text[$i] = "| ". $t;
    }

    writeln("|");
    writeln(implode("\n", $text));
    writeln("|");
    writeln("");
}

function deploy($config)
{
    writeln("------------------------------------------------------------------------------------");
    writeln("- Deploying {$config->name}");
    writeln("------------------------------------------------------------------------------------");
    
    // set directory
    cd($config->home);
    writeln('Checking authentication ...');

    // Checkout branches and switch branch
    run("git fetch");
    run("git checkout {$config->branch}");

    // Reset any existing changes
    $branchStatus = run('git status');
    if (stripos($branchStatus, 'Changes not staged for commit') !== false) {
        writeln('Changes on production detected, resetting git head.');
        $result = run('git reset --hard');
        result($result);
        $result = run('git status');
        result($result);
    }

    // Pull latest changes
    writeln('Pulling latest code from github ...');
    $result = run('git pull');
    result($result);
    writeln('Latest 10 commits:');
    $result = run('git log -10 --pretty=format:"%h - %an, %ar : %s"');
    result($result);

    // check some stuff
    $directory = run('ls -l');
    $doctrine  = run('test -e config/packages/doctrine.yaml && echo 1 || echo 0') === '1';
    
    // Composer update
    if (input()->getArgument('composer')) {
        if (stripos($directory, 'composer.json') !== false) {
            writeln('Updating composer libraries (it is normal for this to take a while)...');
            $result = run('composer update');
            result($result);
        }
    }

    
    // Write version
    writeln('Setting git version+hash');
    run('bash bin/version');

    // Clear symfony cache
    if (stripos($directory, 'symfony.lock') !== false) {
        writeln('Clearing symfony cache ...');
        $result = run('php bin/console cache:warmup') . "\n";
        $result .= run('php bin/console cache:clear') . "\n";
        $result .= run('php bin/console cache:clear --env=prod');
        result($result);

        // Update database schema
        if ($doctrine) {
            writeln('Updating database schema ...');

            // update db
            $result = run('php bin/console doctrine:schema:update --force --dump-sql');
            result($result);

            // ask if we should drop the current db
            /*
            $shouldDropDatabase = askConfirmation('(Symfony) Drop Database?', false);
            if ($shouldDropDatabase) {
                run('php bin/console doctrine:schema:drop --force');
            }
            */
        }
    }

    // Restart supervisord
    writeln('Restart supervisord');
    run('sudo supervisorctl restart all');

    // Finished
    write("Deployed branch {$config->branch} to: {$config->name} environment\n\n");

    // Announce on discord
    #writeln('Posting update to discord');
    #run('php /home/dalamud/mog/bin/console ListCommitChangesCommand');
}

function deploySync($config)
{
    writeln('-> Connecting to sync server');
    // set directory
    cd($config->home);
    
    // Checkout branches and switch branch
    writeln('-> Fetching branches and checking out: '. $config->branch);
    run("git fetch");
    run("git checkout {$config->branch}");
    
    // Reset any existing changes
    $branchStatus = run('git status');
    if (stripos($branchStatus, 'Changes not staged for commit') !== false) {
        writeln('-> Resetting local git copy');
        run('git reset --hard');
        run('git status');
    }
    
    // Pull latest changes
    writeln('-> Pulling latest code from github ...');
    run('git fetch --all');
    run('git reset --hard origin/master');
    $result = run('git pull');
    result($result);
    writeln('-> Latest 10 commits:');
    $result = run('git log -10 --pretty=format:"%h - %an, %ar : %s"');
    result($result);
    
    // composer update
    writeln('-> Updating composer libraries (it is normal for this to take a while)...');
    run('composer require xivapi/lodestone-parser 1.8.18');
    $result = run('composer update');
    result($result);
    
    // clear cache
    writeln('-> Clearing symfony cache ...');
    $result = run('php bin/console cache:warmup') . "\n";
    $result .= run('php bin/console cache:clear') . "\n";
    $result .= run('php bin/console cache:clear --env=prod');
    result($result);
    
    // Restarting supervisord
    writeln('-> Restarting supervisor');
    run('sudo supervisorctl restart all');
    $result = run('sudo supervisorctl status');
    result($result);
}

// run fixes on servers
function fix($config)
{
    writeln('-> Connecting to sync server');
    // set directory
    cd($config->home);
    
    writeln('Running fixes');
    
    run('sudo supervisorctl stop all');
    $result = run('sudo supervisorctl status');
    result($result);
    
    #run("sudo sed -i 's|memory_limit = 128M|memory_limit = 500M|' /etc/php/7.2/fpm/php.ini");
    #run("sudo service php7.2-fpm restart");
    
    #run('sudo /bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024');
    #run('sudo /sbin/mkswap /var/swap.1');
    #run('sudo /sbin/swapon /var/swap.1');
}

// --------------------------------------------------------

task('api', function () {
    deploy((Object)[
        'name'   => 'API',
        'home'   => "/home/dalamud/dalamud/",
        'branch' => 'master',
    ]);
})->onHosts('api');

task('staging', function () {
    deploy((Object)[
        'name'   => 'Staging',
        'home'   => "/home/dalamud/dalamud_staging/",
        'branch' => 'staging',
    ]);
})->onHosts('staging');

task('parser', function () {
    deploy((Object)[
        'name'   => 'Lodestone Parser',
        'home'   => "/home/dalamud/dalamud/",
        'branch' => 'master',
    ]);
})->onHosts('parser');

task('sync', function () {
    deploySync((Object)[
        'name'   => 'Sync',
        'home'   => "/home/dalamud/xivapi.com",
        'branch' => 'master',
    ]);
})->onHosts(
    'Sync',
    'Server1',
    'Server2',
    'Server3',
    'Server4',
    'Server5',
    'Server6',
    'Server7',
    'Server8',
    'Server9',
    'Server10',
    'Server11',
    'Server12'
);

task('fix', function () {
    fix((Object)[
        'name'   => 'Sync',
        'home'   => "/home/dalamud/xivapi.com",
        'branch' => 'rabbitmq',
    ]);
})->onHosts(
    'Sync',
    'Server1',
    'Server2',
    'Server3',
    'Server4',
    'Server5',
    'Server6',
    'Server7',
    'Server8',
    'Server9',
    'Server10',
    'Server11',
    'Server12'
);
