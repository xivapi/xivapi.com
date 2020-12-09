<?php

namespace App\Common\ServicesThirdParty\Github;

use App\Common\Service\Redis\Redis;

class GitHub
{
    /**
     * Grab all github commit history
     */
    public static function getGithubCommitHistory()
    {
        $commits = Redis::Cache()->get('github_commits');

        if (!$commits) {
            $client  = new \Github\Client();

            $commits = (Object)[
                'master'  => $client->api('repo')->commits()->all('xivapi', 'xivapi.com', ['sha' => 'master']),
                'staging' => $client->api('repo')->commits()->all('xivapi', 'xivapi.com', ['sha' => 'staging'])
            ];

            // cache for an hour, I don't commit that often!
            Redis::Cache()->set('github_commits', $commits, 60 * 60);
        }

        return $commits;
    }
}
