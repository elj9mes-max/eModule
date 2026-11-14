<?php

namespace App\Enums;

/**
 * Paper styles a teacher can pick for a lecture page; students read the page on the same paper.
 * Keep in sync with resources/js/Services/paperThemes.js.
 */
enum PaperTheme: string
{
    case Classic = 'classic';
    case Parchment = 'parchment';
    case Emerald = 'emerald';
    case Sky = 'sky';
    case Lined = 'lined';
}
