<?php
namespace Gbo\PhpGithubCli\Module\Webhook;

use Gbo\PhpGithubCli\GithubCommandWebhook;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Github\HttpClient\Message\ResponseMediator;

class Tag extends GithubCommandWebhook
{

    /**
     * Symfony cli module config
     */
    protected function githubConfigure()
    {
        $this
            ->setName('webhook:tag')
            ->setDescription('Trigger tag event to webhook')
            ->addArgument('org', InputArgument::REQUIRED, 'Repo owner')
            ->addArgument('repo', InputArgument::REQUIRED, 'Repo name')
            ->addArgument('pl_url', InputArgument::REQUIRED, 'Payload URL')
            ->addArgument('tag', InputArgument::REQUIRED, 'Tag');
    }

    /**
     * githubExec implementation
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     * @throws \Github\Exception\MissingArgumentException
     */
    protected function githubExec(InputInterface $input, OutputInterface $output)
    {
        
        $DATAS = array();
        $REQUEST_HEADER = [
          "X-GitHub-Event" => "push",
          "User-Agent" => "GitHub-Hookshot/f221634",
          "Content-Type" => "application/json",
        ];
        
        $options = $input->getOptions('branch');
        
        $OWNER = $input->getArgument('org');
        $REPO = $input->getArgument('repo');
        
        $tags = self::$githubClient->api('repo')->tags($OWNER, $REPO);
        $tagg = false;
        foreach ($tags as $tag){
            if ($tag['name'] == $input->getArgument('tag')){
                $tagg = $tag;
                break;
            }
        }
        if (!$tagg){
            throw new \Exception('Tag ' . $input->getArgument('tag') . ' not found.');
        }
        
        $commitID = $tagg['commit']['sha'];
        
        $response = self::$githubClient->getHttpClient()->get("https://api.github.com/repos/$OWNER/$REPO/commits/$commitID/branches-where-head", array ('Accept' => 'application/vnd.github.groot-preview+json'));
        $resp = ResponseMediator::getContent($response);
        
        if (empty($resp)){
          $BRANCH = "refs/heads/master";
        }else{
          $BRANCH = $resp[0]["name"];
        }
        
        $DATAS['base_ref'] = "refs/heads/master";
        $DATAS['ref'] = "refs/tags/" . $input->getArgument('tag');
        
        # Set Commit information
        $COMMIT_INFO = ["author", "committer", "message", "url"];
        $commit = self::$githubClient->api('repo')->commits()->show($input->getArgument('org'),  $input->getArgument('repo'), $commitID);
        
        $commit['commit']['message'] = "[Trigger by ghcli] - " . $commit['commit']['message'];
        
        unset($commit['files']);
        $DATAS['head_commit'] = $DATAS['head_commit'] = array();
        $DATAS['head_commit'] = $DATAS['head_commit'] = array_intersect_key ($commit['commit'], array_flip($COMMIT_INFO));
        $DATAS['head_commit']['id'] = $DATAS['head_commit']['id'] = $commit['sha'];
        $DATAS['head_commit']['id'] = $DATAS['head_commit']['id'] = $commit['sha'];

        $DATAS['after'] = $commit['sha'];
        $DATAS['created'] = $DATAS['deleted'] = $DATAS['forced'] = false;
        $DATAS['commits'] = array();
        
        return [ "datas" => $DATAS, "header" => $REQUEST_HEADER];
    }
    
}
