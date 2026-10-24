<?php

namespace App\Support;

/**
 * Sous-titres au format WebVTT, le seul que lit la balise <track> des navigateurs.
 * Un fichier SRT (le format qu'exportent la plupart des outils) est converti :
 * mêmes blocs, seule la virgule des millisecondes devient un point. Une
 * transcription JSON minutée au mot (ElevenLabs) est découpée en sous-titres.
 */
class WebVtt
{
    /** Le texte WebVTT d'un fichier .vtt ou .srt, ou null s'il ne contient aucun sous-titre minuté. */
    public static function fromSubtitles(string $contents): ?string
    {
        $contents = str_replace(["\r\n", "\r"], "\n", preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents);
        $contents = trim($contents);

        if (str_starts_with($contents, '{')) {
            return self::fromWordTimings($contents);
        }

        if (! str_contains($contents, '-->')) {
            return null;
        }

        if (str_starts_with($contents, 'WEBVTT')) {
            return $contents."\n";
        }

        $contents = preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $contents) ?? $contents;

        return "WEBVTT\n\n".$contents."\n";
    }

    /** Au-delà, un sous-titre devient difficile à lire avant qu'il ne disparaisse. */
    private const int MAX_CUE_LENGTH = 84;

    private const float MAX_CUE_SECONDS = 6.0;

    /**
     * Une transcription JSON minutée au mot (format d'ElevenLabs Scribe : `words` à la
     * racine ou dans chaque `segments[]`), découpée en sous-titres courts : on coupe en
     * fin de phrase, après un silence, ou quand le sous-titre devient trop long.
     */
    private static function fromWordTimings(string $json): ?string
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return null;
        }

        $words = isset($data['segments']) && is_array($data['segments'])
            ? array_merge(...array_map(fn (mixed $segment): array => (array) ($segment['words'] ?? []), $data['segments']))
            : (array) ($data['words'] ?? []);

        $cues = [];
        $current = null;

        foreach ($words as $word) {
            $text = trim((string) ($word['text'] ?? ''));

            if ($text === '' || ($word['type'] ?? 'word') !== 'word' || ! isset($word['start_time'], $word['end_time'])) {
                continue;
            }

            $start = (float) $word['start_time'];
            $end = (float) $word['end_time'];

            if ($current !== null && (
                mb_strlen($current['text'].' '.$text) > self::MAX_CUE_LENGTH
                || $end - $current['start'] > self::MAX_CUE_SECONDS
                || $start - $current['end'] > 1.0
            )) {
                $cues[] = $current;
                $current = null;
            }

            $current = $current === null
                ? ['start' => $start, 'end' => $end, 'text' => $text]
                : [...$current, 'end' => $end, 'text' => $current['text'].' '.$text];

            if (preg_match('/[.?!…]$/u', $text) && mb_strlen($current['text']) > 20) {
                $cues[] = $current;
                $current = null;
            }
        }

        if ($current !== null) {
            $cues[] = $current;
        }

        if ($cues === []) {
            return null;
        }

        $blocks = array_map(
            fn (array $cue): string => self::timestamp($cue['start']).' --> '.self::timestamp($cue['end'])."\n".$cue['text'],
            $cues,
        );

        return "WEBVTT\n\n".implode("\n\n", $blocks)."\n";
    }

    /** 63.26 → « 00:01:03.260 ». */
    private static function timestamp(float $seconds): string
    {
        $milliseconds = (int) round($seconds * 1000);

        return sprintf('%02d:%02d:%02d.%03d', intdiv($milliseconds, 3_600_000), intdiv($milliseconds, 60_000) % 60, intdiv($milliseconds, 1000) % 60, $milliseconds % 1000);
    }
}
