<?php
declare(strict_types=1);

namespace GeorgRinger\GoogleDocsContent\Command;

use GeorgRinger\GoogleDocsContent\Api\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SetupCredentialsCommand extends Command
{

    /**
     * Defines the allowed options for this command
     *
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Setup access')
            ->addArgument('credentialsFile', InputArgument::REQUIRED, 'Path to the credentials');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $credentialsFile = $input->getArgument('credentialsFile');
        if (!is_file($credentialsFile)) {
            $io->error(sprintf('File "%s" does not exist', $credentialsFile));
        } else {
            $t3Client = GeneralUtility::makeInstance(Client::class);
            $config = json_decode(file_get_contents($credentialsFile), true);
            $t3Client->setAuthConfig($config);


            $client = new \Google_Client();
            $client->setApplicationName('Google Docs API PHP Quickstart');
            $client->setScopes(Client::SCOPES);
            $client->setAccessType('offline');
            $client->setAuthConfig($config);

            $question = new Question(sprintf('Open the following link in your browser: %s and enter the verification link:', $client->createAuthUrl()));
            $authCode = $io->askQuestion($question);

            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            $t3Client->setToken($accessToken);

            $client->setAccessToken($accessToken);


            $io->success('Successfully set it up!');
        }

    }
}

