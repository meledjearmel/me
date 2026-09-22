<?php

namespace App\Enums;

enum TestimonialStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
