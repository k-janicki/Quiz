<?php
namespace AppBundle\Custom\Doctrine2;
use Doctrine\DBAL\Platforms\MySQL80Platform;

class CustomDBPlatform extends MySQL80Platform {
    public function getForUpdateSQL()
    {
        //w sumie to nic nie zmienia bo aplikacja i tak czeka na zwolnienie locka zeby moc zrobic operacje
        return 'FOR UPDATE SKIP LOCKED';
    }

}