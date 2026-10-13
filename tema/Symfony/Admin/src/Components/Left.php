<?php

namespace App\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('left')]
class Left
{
	public string $dataCurrentPage = '';
}