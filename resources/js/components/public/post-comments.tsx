import { Form } from '@inertiajs/react';
import { useState } from 'react';
import PostCommentController from '@/actions/App/Http/Controllers/PostCommentController';
import { formatPostDate } from '@/components/public/post-card';
import { useLocale, useTranslations } from '@/lib/i18n';

export type PublicComment = {
    id: number;
    author_name: string;
    body: string;
    created_at: string;
};

/** Commentaires approuvés de l'article, puis le formulaire (publié après modération). */
export default function PostComments({
    slug,
    comments,
}: {
    slug: string;
    comments: PublicComment[];
}) {
    const t = useTranslations();
    const locale = useLocale();
    const [sent, setSent] = useState(false);

    return (
        <section className="pub-comments" aria-labelledby="pub-comments-title">
            <h2 id="pub-comments-title" className="pub-comments__title">
                {t.blog.comments.title(comments.length)}
            </h2>

            {comments.length > 0 && (
                <ol className="pub-comments__list">
                    {comments.map((comment) => (
                        <li key={comment.id} className="pub-comments__item">
                            <p className="pub-comments__meta">
                                <strong>{comment.author_name}</strong>
                                <time dateTime={comment.created_at}>
                                    {formatPostDate(comment.created_at, locale)}
                                </time>
                            </p>
                            <p className="pub-comments__body">{comment.body}</p>
                        </li>
                    ))}
                </ol>
            )}

            {sent ? (
                <p className="pub-post__notice" role="status">
                    {t.blog.comments.sent}
                </p>
            ) : (
                <Form
                    {...PostCommentController.store.form({
                        locale,
                        post: slug,
                    })}
                    options={{ preserveScroll: true }}
                    className="pub-comments__form"
                    resetOnSuccess
                    onSuccess={() => setSent(true)}
                >
                    {({ processing, errors }) => (
                        <>
                            {/* Piège anti-spam : jamais rempli par un humain. */}
                            <input
                                type="text"
                                name="website"
                                tabIndex={-1}
                                autoComplete="off"
                                hidden
                                aria-hidden="true"
                            />
                            <div className="pub-comments__row">
                                <label className="pub-drawer__field">
                                    <span>{t.blog.comments.name}</span>
                                    <input
                                        type="text"
                                        name="author_name"
                                        autoComplete="name"
                                        maxLength={80}
                                        required
                                    />
                                    {errors.author_name && (
                                        <small>{errors.author_name}</small>
                                    )}
                                </label>
                                <label className="pub-drawer__field">
                                    <span>{t.blog.comments.email}</span>
                                    <input
                                        type="email"
                                        name="author_email"
                                        autoComplete="email"
                                    />
                                    {errors.author_email && (
                                        <small>{errors.author_email}</small>
                                    )}
                                </label>
                            </div>
                            <label className="pub-drawer__field">
                                <span>{t.blog.comments.body}</span>
                                <textarea
                                    name="body"
                                    maxLength={2000}
                                    required
                                />
                                {errors.body && <small>{errors.body}</small>}
                            </label>
                            <p className="pub-newsletter__hint">
                                {t.blog.comments.hint}
                            </p>
                            <button
                                type="submit"
                                className="pub-project-cta__button"
                                disabled={processing}
                            >
                                {t.blog.comments.submit}
                                <span aria-hidden="true">→</span>
                            </button>
                        </>
                    )}
                </Form>
            )}
        </section>
    );
}
