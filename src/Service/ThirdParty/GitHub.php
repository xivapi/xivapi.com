<?php

namespace App\Service\ThirdParty;

use App\Service\Redis\Cache;

class GitHub
{
    /**
     * Grab all github commit history
     */
    public static function getGithubCommitHistory()
    {
        $key     = 'github_commits';
        $cache   = new Cache();
        $commits = $cache->get($key);

        if (!$commits) {
            $client  = new \Github\Client();

            $commits = (Object)[
                'master'  => $client->api('repo')->commits()->all('xivapi', 'xivapi.com', ['sha' => 'master']),
                'staging' => $client->api('repo')->commits()->all('xivapi', 'xivapi.com', ['sha' => 'staging'])
            ];

            // cache for an hour, I don't commit that often!
            $cache->set($key, $commits, 60*60);
        }

        return $commits;
    }
}
