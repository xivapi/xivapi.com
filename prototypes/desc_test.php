<?php

require __DIR__ . '/../vendor/autoload.php';

$desc = new \App\Service\Helpers\DescriptionFormatter();

$result = $desc->format('"Increases enmity in all nearby enemies.
<72>F201F8</72><73>F201F9</73>Additional Effect:<73>01</73><72>01</72> <72>F201FA</72><73>F201FB</73>Blind<73>01</73><72>01</72>
<72>F201F8</72><73>F201F9</73>Duration:<73>01</73><72>01</72> 12s"
');

print_r($result);
