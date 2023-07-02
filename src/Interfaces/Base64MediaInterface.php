<?php

namespace Interfaces;

interface Base64MediaInterface
{
    public function setDisplayName(?string $displayName): void;
    public function setMimeType(?string $mimeType): void;
}
