<?php

/*
 * This file is part of StyleCI.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StyleCI\StyleCI\GitHub;

use Github\ResultPager;
use Illuminate\Contracts\Cache\Repository;
use StyleCI\StyleCI\Models\Repo;
use StyleCI\StyleCI\Models\User;

/**
 * This is the github collaborators class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class Collaborators
{
    /**
     * The github client factory instance.
     *
     * @var \StyleCI\StyleCI\GitHub\ClientFactory
     */
    protected $factory;

    /**
     * The illuminate cache repository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new github collaborators instance.
     *
     * @param \StyleCI\StyleCI\GitHub\ClientFactory  $factory
     * @param \Illuminate\Contracts\Cache\Repository $cache
     *
     * @return void
     */
    public function __construct(ClientFactory $factory, Repository $cache)
    {
        $this->factory = $factory;
        $this->cache = $cache;
    }

    /**
     * Get the collaborators for a repo.
     *
     * @param \StyleCI\StyleCI\Models\Repo $repo
     *
     * @return int[]
     */
    public function get(Repo $repo)
    {
        // cache the collaborator info from github for 12 hours
        return $this->cache->remember("{$repo->id}collaborators", 720, function () use ($repo) {
            return $this->fetchFromGitHub($repo->user, $repo->name);
        });
    }

    /**
     * Fetch a repo's collaborators from github.
     *
     * @param \StyleCI\StyleCI\Models\User $user
     * @param string                       $name
     *
     * @return array
     */
    protected function fetchFromGitHub(User $user, $name)
    {
        $client = $this->factory->make($user);
        $paginator = new ResultPager($client);

        $list = [];

        foreach ($paginator->fetchAll($client->repo()->collaborators(), 'all', explode('/', $name)) as $user) {
            $list[] = $user['id'];
        }

        return $list;
    }

    /**
     * Flush our cache of the repo's collaborators.
     *
     * @param \StyleCI\StyleCI\Models\Repo $repo
     *
     * @return void
     */
    public function flush(Repo $repo)
    {
        $this->cache->forget("{$repo->id}collaborators");
    }
}
