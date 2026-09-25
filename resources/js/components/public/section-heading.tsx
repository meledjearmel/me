export default function SectionHeading({
    kicker,
    title,
    hook,
}: {
    kicker: string;
    title: string;
    hook?: string;
}) {
    return (
        <header>
            <p>{kicker}</p>
            <h2>{title}</h2>
            {hook && <p>{hook}</p>}
        </header>
    );
}
