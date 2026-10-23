<?php

use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colonne de rattachement => type polymorphe enregistré dans les tables de liaison.
     *
     * @var array<string, class-string>
     */
    private const array SUBJECTS = [
        'project_id' => Project::class,
        'experience_id' => Experience::class,
        'education_id' => Education::class,
    ];

    /**
     * Un avis (et un lien d'invitation) peut porter sur plusieurs projets, expériences et
     * formations à la fois ; un lien peut aussi servir à plusieurs personnes (max_uses).
     */
    public function up(): void
    {
        foreach (['testimonial', 'review_invitation'] as $owner) {
            Schema::create("{$owner}_subjects", function (Blueprint $table) use ($owner) {
                $table->foreignId("{$owner}_id")->constrained()->cascadeOnDelete();
                $table->morphs('subject');
                $table->primary(["{$owner}_id", 'subject_type', 'subject_id']);
            });
        }

        $this->copySubjects('testimonials', 'testimonial');
        $this->copySubjects('review_invitations', 'review_invitation');

        Schema::table('testimonials', function (Blueprint $table) {
            // Lien d'invitation par lequel l'avis est arrivé (plusieurs avis pour un lien collectif).
            $table->foreignId('review_invitation_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('review_invitations', function (Blueprint $table) {
            // 1 = lien personnel ; plus = lien collectif ; vide = sans limite.
            $table->unsignedSmallInteger('max_uses')->nullable()->default(1)->after('note');
            $table->unsignedSmallInteger('uses_count')->default(0)->after('max_uses');
        });

        DB::table('review_invitations')->whereNotNull('testimonial_id')->orderBy('id')->each(function (object $invitation): void {
            DB::table('testimonials')->where('id', $invitation->testimonial_id)->update(['review_invitation_id' => $invitation->id]);
            DB::table('review_invitations')->where('id', $invitation->id)->update(['uses_count' => 1]);
        });

        Schema::table('review_invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('testimonial_id');
        });

        foreach (['testimonials', 'review_invitations'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                foreach (array_keys(self::SUBJECTS) as $column) {
                    $table->dropConstrainedForeignId($column);
                }
            });
        }
    }

    /**
     * Reverse the migrations. Seul le premier rattachement de chaque type est conservé.
     */
    public function down(): void
    {
        foreach (['testimonials', 'review_invitations'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('experience_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('education_id')->nullable()->constrained('educations')->nullOnDelete();
            });
        }

        Schema::table('review_invitations', function (Blueprint $table) {
            $table->foreignId('testimonial_id')->nullable()->constrained()->nullOnDelete();
        });

        DB::table('testimonials')->whereNotNull('review_invitation_id')->orderBy('id')->each(function (object $testimonial): void {
            DB::table('review_invitations')->where('id', $testimonial->review_invitation_id)->update(['testimonial_id' => $testimonial->id]);
        });

        foreach (['testimonial' => 'testimonials', 'review_invitation' => 'review_invitations'] as $owner => $table) {
            foreach (self::SUBJECTS as $column => $type) {
                DB::table("{$owner}_subjects")->where('subject_type', $type)->orderBy("{$owner}_id")->each(function (object $row) use ($owner, $table, $column): void {
                    DB::table($table)->where('id', $row->{"{$owner}_id"})->whereNull($column)->update([$column => $row->subject_id]);
                });
            }
        }

        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('review_invitation_id');
        });

        Schema::table('review_invitations', function (Blueprint $table) {
            $table->dropColumn(['max_uses', 'uses_count']);
        });

        Schema::dropIfExists('testimonial_subjects');
        Schema::dropIfExists('review_invitation_subjects');
    }

    private function copySubjects(string $table, string $owner): void
    {
        foreach (self::SUBJECTS as $column => $type) {
            DB::table($table)->whereNotNull($column)->orderBy('id')->each(function (object $row) use ($owner, $column, $type): void {
                DB::table("{$owner}_subjects")->insert([
                    "{$owner}_id" => $row->id,
                    'subject_type' => $type,
                    'subject_id' => $row->{$column},
                ]);
            });
        }
    }
};
