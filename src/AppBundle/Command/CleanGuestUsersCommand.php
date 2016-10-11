<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanGuestUsersCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('mco:guest:clean')
            ->setDescription('Remove inactive guest users')
            ->setHelp(<<<EOT
The <info>mco:guest:clean</info> command remove inactive guest users whom did not log in during the last xx days (31 by default):
  <info>php app/console mco:guest:clean 31</info>
EOT
            )
            ->addArgument('inactive_days', InputArgument::OPTIONAL, 'Number of days of inactivity');
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Remove inactive guest users');

        // Days by default if not set
        $days = $input->getArgument('inactive_days');
        if (!$days) {
            $days = 31;
        }

        // Set date for search query
        $date = new \DateTime(date('Y-m-d', strtotime('-' . $days . ' days')));

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $query = $em->createQueryBuilder()->select('u')
            ->from('AppBundle:User', 'u')
            ->where('u.lastLogin < :date')
            ->setParameter('date', $date)
            ->getQuery();

        $deletedUsers = 0;

        foreach ($query->getResult() as $user) {
            if ($user->hasRole('ROLE_GUEST')) {
                $em->remove($user);
                $deletedUsers++;
            }
        }

        // Delete users in DB
        $em->flush();

        $io->success($deletedUsers . ' deleted users');
    }
}
