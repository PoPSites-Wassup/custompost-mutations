<?php

declare(strict_types=1);

namespace PoPSitesWassup\CustomPostMutations\MutationResolvers;

class MutationInputProperties extends \PoPSchema\CustomPostMutations\MutationResolvers\MutationInputProperties
{
    public const REFERENCES = 'references';
    public const TOPICS = 'topics';
    public const VOLUNTEERSNEEDED = 'volunteersneeded';
    public const APPLIESTO = 'appliesto';
}
