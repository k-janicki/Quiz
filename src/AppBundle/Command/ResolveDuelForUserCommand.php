<?php

namespace AppBundle\Command;

use AppBundle\Service\ResolveDuelService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResolveDuelForUserCommand extends Command
{
    use LockableTrait;
    protected static $defaultName = 'resolve:duel:for_user';
    private ResolveDuelService $duelService;

    public function __construct(ResolveDuelService $duelService)
    {
        $this->duelService = $duelService;

        parent::__construct();
    }
    protected function configure()
    {
        $this->setDescription('Command do przypisania usera do pojedynku dla quizu')
            ->addArgument(
                'userId',
                InputArgument::REQUIRED,
                'ID uzytkownika dla przypisania'
            )
            ->addArgument(
                'quizId',
                InputArgument::REQUIRED,
                'ID quizu dla przypisania'
            )
            ->addArgument(
                'resolveMethod',
                InputArgument::REQUIRED,
                'Metoda wykorzystana do przypisania pojedynku'
            )
            ->setHelp(
                'Komenda pozwala na przypisanie uzytkownika o wskazanym id, do pojeydnku dla quizu o wskazanym id'.PHP_EOL.
                'Konieczne jest wskazanie metody -o - optmisticlock; -p - pessimisticlock; -q - kolejka'
            )
            ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock(static::class)) {
            $output->writeln('ResolveDuelForUserCommand: The command is already running in another process.');
            return 1;
        }
        $resolveMethod = $input->getArgument('resolveMethod');
        $userId = $input->getArgument('userId');
        $quizId = $input->getArgument('quizId');
        $returned = null;
        switch ($resolveMethod) {
            case 'o':
                $tryIndex = 0;
                do {
                    $returned = $this->duelService->resolveDuelOptimistic($quizId, $userId, $tryIndex);
//                    $returned = $this->duelService->getDuelFromQueue($quiz->getId(), $user->getId());
                    $tryIndex += 1;
                } while ($returned !== 0 && $tryIndex <= 3);
                break;
            case 'p':
                $tryIndex = 0;
                do {
                    $returned = $this->duelService->resolveDuelPessimistic2($quizId, $userId, $tryIndex);
                    $tryIndex += 1;
                } while ($returned !== 0 && $tryIndex <= 3);
                break;
            case 'q':
                $tryIndex = 0;
                do {
                    $returned = $this->duelService->getDuelFromQueue($quizId, $userId);
                    $tryIndex += 1;
                } while ($returned !== 0 && $tryIndex <= 3);
                break;
            default:
                $returned = -1;
                break;
        }
        if ($returned === 0) {
            $output->writeln('ResolveDuelForUserCommand: Poprawnie przypisanio użytkownika do pojedynku.');
            return 0;
        } else {
            $output->writeln('ResolveDuelForUserCommand: Nie udało się przypisać użytkownika do pojedynku.');
            return 1;
        }

    }
}