<?php

namespace App\Enums;

enum InterviewType: string
{
    case Phone = 'phone';
    case Video = 'video';
    case InPerson = 'in_person';
    case Technical = 'technical';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Phone',
            self::Video => 'Video',
            self::InPerson => 'In Person',
            self::Technical => 'Technical',
        };
    }
}
