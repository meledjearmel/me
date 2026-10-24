<?php

namespace App\Http\Controllers;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use Illuminate\Http\Response;

class TestimonialCaptionController extends Controller
{
    /**
     * Les sous-titres d'une vidéo d'avis, servis en text/vtt (type exigé par la
     * balise <track>), pour les avis approuvés seulement.
     */
    public function __invoke(Testimonial $testimonial, string $language): Response
    {
        abort_unless($testimonial->status === TestimonialStatus::Approved, 404);

        $captions = $testimonial->getTranslation('video_captions', $language, false);

        abort_if(blank($captions), 404);

        return response($captions, 200, [
            'Content-Type' => 'text/vtt; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
