<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$em         = $container->get('doctrine')->getEntityManager('default');
$con        = $em->getConnection();

$con = $em->getConnection();

$query = "SELECT * FROM campaign";
$statement = $con->prepare($query);
$statement->execute();
$campaigns = $statement->fetchAll();

$output->writeln("<info>" . count($campaigns) . " campanhas encontradas</info>");

$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, count($campaigns));
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

foreach ($campaigns as $campaign) {
    $campaignEntity = $em->getRepository('DomainBundle:Campaign')->findOneBy(['id' => $campaign['id']]);
    $client = $campaignEntity->getClient();

    if ((boolean)$campaign['pre_login']) {
        $imageMedia = new \Wideti\DomainBundle\Entity\CampaignMediaImage();
        $imageMedia->setCampaign($campaignEntity);
        $imageMedia->setClient($client);
        $imageMedia->setStep(\Wideti\DomainBundle\Entity\Campaign::STEP_PRE_LOGIN);
        $imageMedia->setExhibitionTime($campaign['pre_login_image_time']);
        $imageMedia->setFullSize($campaign['pre_full_size']);
        $imageMedia->setImageDesktop($campaign['pre_login_image_desktop']);
        $imageMedia->setImageMobile($campaign['pre_login_image_mobile']);

        $em->persist($imageMedia);
        $em->flush();
    }

    if ((boolean)$campaign['pos_login']) {
        $imageMedia = new \Wideti\DomainBundle\Entity\CampaignMediaImage();
        $imageMedia->setCampaign($campaignEntity);
        $imageMedia->setClient($client);
        $imageMedia->setStep(\Wideti\DomainBundle\Entity\Campaign::STEP_POS_LOGIN);
        $imageMedia->setExhibitionTime($campaign['pos_login_image_time']);
        $imageMedia->setFullSize($campaign['pos_full_size']);
        $imageMedia->setImageDesktop($campaign['pos_login_image_desktop']);
        $imageMedia->setImageMobile($campaign['pos_login_image_mobile']);

        $em->persist($imageMedia);
        $em->flush();
    }

    $progressBar->advance();
    $em->clear();
}