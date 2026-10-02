<?php

namespace App\Jobs;

use App\Models\Testimonial;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Prépare la vidéo d'un avis pour le web, en tâche de fond : compression en MP4
 * H.264 (720p au plus, lecture qui démarre avant la fin du téléchargement),
 * image d'aperçu et durée. Sans ffmpeg sur la machine, la vidéo reste servie
 * telle qu'envoyée, sans aperçu : le job ne fait jamais échouer la file.
 */
class ProcessTestimonialVideo implements ShouldQueue
{
    use Dispatchable, Queueable;

    /** Un seul essai : un échec de ffmpeg se reproduirait à l'identique. */
    public int $tries = 1;

    /** Une vidéo de téléphone de quelques minutes peut prendre un moment à compresser. */
    public int $timeout = 900;

    /** La vidéo a été remplacée ou retirée entre-temps : plus rien à faire. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Media $video) {}

    public function handle(): void
    {
        if (! $this->ffmpegIsAvailable()) {
            Log::warning('Vidéo d\'avis non traitée : ffmpeg est introuvable.', ['media' => $this->video->id]);

            return;
        }

        $testimonial = $this->video->model;

        if (! $testimonial instanceof Testimonial) {
            return;
        }

        $workdir = storage_path('app/private/video-processing/'.Str::uuid());
        File::ensureDirectoryExists($workdir);

        try {
            $output = $workdir.'/'.pathinfo($this->video->file_name, PATHINFO_FILENAME).'.mp4';
            $poster = $workdir.'/poster.jpg';

            $this->transcode($this->video->getPath(), $output);
            $this->extractPoster($output, $poster);
            $metadata = $this->probe($output);

            $testimonial->addMedia($output)
                ->withCustomProperties([...$metadata, 'processed' => true])
                ->toMediaCollection(Testimonial::VIDEO_COLLECTION);

            $testimonial->addMedia($poster)
                ->usingFileName('poster-'.$testimonial->id.'.jpg')
                ->toMediaCollection(Testimonial::POSTER_COLLECTION);
        } finally {
            File::deleteDirectory($workdir);
        }
    }

    private function ffmpegIsAvailable(): bool
    {
        try {
            return Process::run([$this->ffmpeg(), '-version'])->successful()
                && Process::run([$this->ffprobe(), '-version'])->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function ffmpeg(): string
    {
        return config('media-library.ffmpeg_path');
    }

    private function ffprobe(): string
    {
        return config('media-library.ffprobe_path');
    }

    /** Le plus petit côté passe à 720px au plus, que la vidéo soit en portrait ou en paysage. */
    private function transcode(string $input, string $output): void
    {
        Process::timeout($this->timeout)->run([
            $this->ffmpeg(), '-y', '-i', $input,
            '-vf', "scale='if(gt(iw,ih),-2,min(720,iw))':'if(gt(iw,ih),min(720,ih),-2)'",
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '26', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-b:a', '128k',
            '-movflags', '+faststart',
            $output,
        ])->throw();
    }

    private function extractPoster(string $video, string $poster): void
    {
        Process::run([$this->ffmpeg(), '-y', '-ss', '1', '-i', $video, '-frames:v', '1', '-q:v', '3', $poster])->throw();
    }

    /**
     * @return array{duration: int, width: int, height: int}
     */
    private function probe(string $video): array
    {
        $result = Process::run([
            $this->ffprobe(), '-v', 'error', '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height:format=duration',
            '-of', 'json', $video,
        ])->throw();

        /** @var array{streams?: array<int, array{width?: int, height?: int}>, format?: array{duration?: string}} $data */
        $data = json_decode($result->output(), true) ?? [];

        return [
            'duration' => (int) round((float) ($data['format']['duration'] ?? 0)),
            'width' => (int) ($data['streams'][0]['width'] ?? 0),
            'height' => (int) ($data['streams'][0]['height'] ?? 0),
        ];
    }
}
