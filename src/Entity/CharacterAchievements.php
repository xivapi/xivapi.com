<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="lodestone_character_achievements",
 *     indexes={
 *          @ORM\Index(name="state", columns={"state"}),
 *          @ORM\Index(name="updated", columns={"updated"}),
 *          @ORM\Index(name="priority", columns={"priority"}),
 *          @ORM\Index(name="notFoundChecks", columns={"not_found_checks"}),
 *          @ORM\Index(name="achievementsPrivateChecks", columns={"achievements_private_checks"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CharacterAchievementRepository")
 */
class CharacterAchievements extends Entity
{

}
