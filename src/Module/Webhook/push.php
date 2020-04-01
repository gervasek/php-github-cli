<?php
namespace Gbo\PhpGithubCli\Module\Webhook;

use Gbo\PhpGithubCli\GithubCommandWebhook;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Push extends GithubCommandWebhook
{

    /**
     * Symfony cli module config
     */
    protected function githubConfigure()
    {
        $this
            ->setName('webhook:push')
            ->setDescription('Trigger push event to webhook')
            ->addArgument('org', InputArgument::REQUIRED, 'Repo owner')
            ->addArgument('repo', InputArgument::REQUIRED, 'Repo name')
            ->addArgument('pl_url', InputArgument::REQUIRED, 'Payload URL')
            ->addOption('branch', 'b', InputOption::VALUE_OPTIONAL, 'Branch name (default: master branch')
            ->addOption('commit', 'c', InputOption::VALUE_OPTIONAL, 'Commit ID (default: last commit)');
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
        
        $REQUEST_HEADER = [
          "X-GitHub-Event" => "push",
          "User-Agent" => "GitHub-Hookshot/f221634",
          "Content-Type" => "application/json",
        ];
        
        $options = $input->getOptions('branch');
        
        if (empty($options['branch'])){
            $branch = "master";
        }else{
            $branch = $options['branch'];
        }
        
        if (empty($options['commit'])){
            $commits = self::$githubClient->api('repo')->commits()->all($input->getArgument('org'),  $input->getArgument('repo'), array('sha' => $branch));
            if (isset($commits[0]) && isset($commits[0]['sha'])){
              $commitID = $commits[0]['sha'];
            }
        }else{
            $commitID = $options['commit'];
        }
        
        # Set ref informations
        $DATAS['base_ref'] = null;
        $DATAS['ref'] = $branch;
        
        # Set Commit information
        $COMMIT_INFO = ["author", "committer", "message", "url"];
        $commit = self::$githubClient->api('repo')->commits()->show($input->getArgument('org'),  $input->getArgument('repo'), $commitID);
        
        $commit['commit']['message'] = "[Trigger by ghcli] - " . $commit['commit']['message'];
        
        unset($commit['files']);
        $DATAS['commits'] = $DATAS['head_commit'] = array();
        $DATAS['commits'] = $DATAS['head_commit'] = array_intersect_key ($commit['commit'], array_flip($COMMIT_INFO));
        $DATAS['commits']['id'] = $DATAS['head_commit']['id'] = $commit['sha'];
        $DATAS['commits']['id'] = $DATAS['head_commit']['id'] = $commit['sha'];
        
        return [ "datas" => $DATAS, "header" => $REQUEST_HEADER];
    }

}
